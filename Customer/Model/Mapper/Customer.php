<?php
/**
 * RocketWeb
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category  RocketWeb
 * @package   MageOS_NetSuiteConnector
 * @copyright Copyright (c) 2026 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    Rocket Web Inc.
 *
 *
 */

namespace MageOS\NetSuiteConnector\Customer\Model\Mapper;

use function count;
use Exception;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\ResourceModel\AddressRepository as MagentoAddressRepository;
use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Magento\Directory\Api\CountryInformationAcquirerInterfaceFactory;
use Magento\Directory\Model\Country;
use Magento\Directory\Model\ResourceModel\Country\Collection;
use Magento\Directory\Model\ResourceModel\Country\CollectionFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderAddressRepositoryInterface;
use Magento\Sales\Model\Order\AddressRepository;
use Magento\Store\Model\StoreManagerInterface;
use NetSuite\Classes\AddRequest;
use NetSuite\Classes\ContactSearchBasic;
use NetSuite\Classes\CustomerAddressbook;
use NetSuite\Classes\CustomerAddressbookList;
use NetSuite\Classes\CustomerSearch;
use NetSuite\Classes\CustomerSearchBasic;
use NetSuite\Classes\CustomerStage;
use NetSuite\Classes\CustomFieldList;
use NetSuite\Classes\GetRequest;
use NetSuite\Classes\RecordRef;
use NetSuite\Classes\RecordType;
use NetSuite\Classes\SearchRequest;
use NetSuite\Classes\SearchStringField;
use NetSuite\Classes\SearchStringFieldOperator;
use NetSuite\Classes\StringCustomFieldRef;
use MageOS\NetSuiteConnector\Core\Helper\Transform;
use MageOS\NetSuiteConnector\Core\Exception\ConnectorRuntimeException;

/**
 * Class Customer
 */
class Customer
{
    /** @var AddressRepositoryInterface */
    protected $addressRepositoryInterface;
    /** @var CountryInformationAcquirerInterface */
    protected $countryInformationAcquirerInterfaceFactory;
    /** @var Transform */
    protected $transformHelper;
    /** @var ManagerInterface */
    protected $eventManager;
    /** @var CustomerInterfaceFactory */
    protected $customerInterface;
    /** @var OrderAddressRepositoryInterface */
    protected $orderAddressRepositoryInterface;
    /** @var CustomerRepositoryInterface */
    protected $customerRepositoryInterface;
    /** @var FilterBuilder */
    protected $filterBuilder;
    /** @var SearchCriteriaBuilder */
    protected $searchCriteriaBuilder;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var CustomerInterfaceFactory
     */
    private $customerInterfaceFactory;
    /**
     * @var AddressInterfaceFactory
     */
    private $addressInterfaceFactory;
    /**
     * @var MagentoAddressRepository
     */
    private $addressRepository;
    /**
     * @var Address
     */
    private $addressHelper;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var AddressRepository
     */
    private $orderAddressRepository;
    /**
     * @var CollectionFactory
     */
    private $countryCollectionFactory;
    /**
     * @var PriceLevel
     */
    private $priceLevel;

    /**
     * Cache customers fetched from NetSuite here.
     * Had to add it to complete integration test, but will result in less queries to NS when
     * exporting many orders from different customer :)
     * @var array
     */
    private $customerCache;
    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management
     */
    private $serviceManagement;

    /**
     * Customer constructor.
     * @param AddressRepositoryInterface $addressRepositoryInterface
     * @param CountryInformationAcquirerInterfaceFactory $countryInformationAcquirerInterfaceFactory
     * @param Transform $transformHelper
     * @param Address $addressHelper
     * @param Context $context
     * @param CustomerInterfaceFactory $customerInterface
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param FilterBuilder $filterBuilder
     * @param StoreManagerInterface $storeManager
     * @param CustomerInterfaceFactory $customerInterfaceFactory
     * @param AddressInterfaceFactory $addressInterfaceFactory
     * @param MagentoAddressRepository $addressRepository
     * @param AddressRepository $orderAddressRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CollectionFactory $countryCollectionFactory
     * @param PriceLevel $priceLevel
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement,
        AddressRepositoryInterface $addressRepositoryInterface,
        CountryInformationAcquirerInterfaceFactory $countryInformationAcquirerInterfaceFactory,
        Transform $transformHelper,
        Address $addressHelper,
        Context $context,
        CustomerInterfaceFactory $customerInterface,
        CustomerRepositoryInterface $customerRepositoryInterface,
        FilterBuilder $filterBuilder,
        StoreManagerInterface $storeManager,
        CustomerInterfaceFactory $customerInterfaceFactory,
        AddressInterfaceFactory $addressInterfaceFactory,
        MagentoAddressRepository $addressRepository,
        AddressRepository $orderAddressRepository,
        ScopeConfigInterface $scopeConfig,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CollectionFactory $countryCollectionFactory,
        PriceLevel $priceLevel
    ) {
        $this->addressRepositoryInterface = $addressRepositoryInterface;
        $this->countryInformationAcquirerInterfaceFactory = $countryInformationAcquirerInterfaceFactory;
        $this->transformHelper = $transformHelper;
        $this->eventManager = $context->getEventDispatcher();
        $this->customerInterface = $customerInterface;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->countryCollectionFactory = $countryCollectionFactory;
        $this->storeManager = $storeManager;
        $this->customerInterfaceFactory = $customerInterfaceFactory;
        $this->addressInterfaceFactory = $addressInterfaceFactory;
        $this->addressRepository = $addressRepository;
        $this->addressHelper = $addressHelper;
        $this->scopeConfig = $scopeConfig;
        $this->orderAddressRepository = $orderAddressRepository;
        $this->priceLevel = $priceLevel;
        $this->serviceManagement = $serviceManagement;
    }

    /**
     * @param $customer
     * @return string
     */
    public function getExternalId($customer): string
    {
        return $customer->getEmail() . '_' . $customer->getStoreId();
    }

    /**
     * @param $order
     * @return string
     */
    public function getExternalIdFromOrder($order): string
    {
        return $order->getCustomerEmail() . '_' . $order->getStoreId();
    }

    /**
     * @param CustomerInterface $magentoCustomer
     * @return \NetSuite\Classes\Customer
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    public function getNetsuiteFormat(
        CustomerInterface $magentoCustomer
    ): \NetSuite\Classes\Customer {
        $netsuiteCustomer = new \NetSuite\Classes\Customer();

        $netsuiteCustomer->externalId = $this->getExternalId($magentoCustomer);
        $netsuiteCustomer->entityId = $this->getExternalId($magentoCustomer);
        $netsuiteCustomer->salutation = $magentoCustomer->getPrefix();

        $middleName = $magentoCustomer->getMiddlename() === '-' ? null : $magentoCustomer->getMiddlename();
        $netsuiteCustomer->firstName = $this->limitTo32Chars($magentoCustomer->getFirstname());
        $netsuiteCustomer->lastName = $this->limitTo32Chars($magentoCustomer->getLastname());
        $netsuiteCustomer->middleName = $middleName;

        $phoneAttr = $magentoCustomer->getCustomAttribute('telephone');
        $netsuiteCustomer->phone = $this->addressHelper->sanitizePhoneNumber($phoneAttr);

        $faxAttr = $magentoCustomer->getCustomAttribute('fax');
        $netsuiteCustomer->fax = $this->addressHelper->sanitizePhoneNumber($faxAttr);

        $netsuiteCustomer->email = $magentoCustomer->getEmail();
        $netsuiteCustomer->vatRegNumber = $magentoCustomer->getTaxvat();
        $netsuiteCustomer->stage = CustomerStage::_customer;
        $netsuiteCustomer->isPerson = true;

        if ($this->priceLevel->getPriceLevelByGroupId($magentoCustomer->getGroupId())) {
            $netsuiteCustomer->priceLevel = new RecordRef();
            $netsuiteCustomer->priceLevel->internalId =
                $this->priceLevel->getPriceLevelByGroupId($magentoCustomer->getGroupId());
        }

        $billingAddressId = $magentoCustomer->getDefaultBilling();
        if ($billingAddressId) {
            $billingAddress = $this->addressRepositoryInterface->getById($billingAddressId);
            if ($billingAddress) {
                $netsuiteCustomer->companyName = $billingAddress->getCompany();
                if (!$magentoCustomer->getCustomAttribute('telephone')) {
                    $netsuiteCustomer->phone = $this->addressHelper->sanitizePhoneNumber(
                        $billingAddress->getTelephone()
                    );
                }
                if (!$magentoCustomer->getCustomAttribute('fax')) {
                    $netsuiteCustomer->fax = $this->addressHelper->sanitizePhoneNumber($billingAddress->getFax());
                }
            }
        }

        $netsuiteCustomer->addressbookList = $this->getAddressBookInNetsuiteFormat($magentoCustomer);

        // Add phone from address if it is empty
        if (!$netsuiteCustomer->phone) {
            $defaultId = $magentoCustomer->getDefaultBilling() ? $magentoCustomer->getDefaultBilling() :
                ($magentoCustomer->getDefaultShipping() ? $magentoCustomer->getDefaultShipping() : false);
            $addresses = $magentoCustomer->getAddresses();
            $phone = null;
            foreach ($addresses as $magentoAddress) {
                if (!$defaultId) {
                    $phone = $this->addressHelper->sanitizePhoneNumber($magentoAddress->getTelephone());
                    break;
                } else {
                    if ($magentoAddress->getId() == $defaultId) {
                        $phone = $this->addressHelper->sanitizePhoneNumber($magentoAddress->getTelephone());
                    }
                }
            }
            $netsuiteCustomer->phone = $phone;
        }

        return $netsuiteCustomer;
    }

    /**
     * @param $by_field
     * @param $search_string
     * @return bool
     * @throws ConnectorRuntimeException
     * @throws Exception
     */
    public function findNetsuiteCustomer($by_field, $search_string)
    {
        $searchField = new SearchStringField();
        $searchField->operator = SearchStringFieldOperator::is;
        $searchField->searchValue = $search_string;

        $isPersonCondition = new \NetSuite\Classes\SearchBooleanField();
        $isPersonCondition->searchValue = true;
        $isInactiveCondition = new \NetSuite\Classes\SearchBooleanField();
        $isInactiveCondition->searchValue = false;

        $search = new CustomerSearchBasic();
        $search->$by_field = $searchField;
        $search->isInactive = $isInactiveCondition;
        $search->isPerson = $isPersonCondition;

        $request = new SearchRequest();
        $request->searchRecord = $search;

        $netsuiteService = $this->serviceManagement->get();

        $searchResponse = $netsuiteService->search($request);
        if (!$searchResponse) {
            throw new ConnectorRuntimeException('NULL Response from NetSuite');
        }

        if ($searchResponse->searchResult->totalRecords != 0) {
            return $searchResponse->searchResult->recordList->record[0]->internalId;
        }

        return false;
    }

    /**
     * @param $email
     * @return bool
     * @throws Exception
     */
    public function findNetsuiteCustomerByContactEmail($email)
    {
        $searchField = new SearchStringField();
        $searchField->operator = SearchStringFieldOperator::is;
        $searchField->searchValue = $email;

        $isPersonCondition = new \NetSuite\Classes\SearchBooleanField();
        $isPersonCondition->searchValue = true;
        $isInactiveCondition = new \NetSuite\Classes\SearchBooleanField();
        $isInactiveCondition->searchValue = false;
        $search = new CustomerSearchBasic();
        $search->email = $searchField;
        $search->isInactive = $isInactiveCondition;
        $search->isPerson = $isPersonCondition;

        $customerSearch = new CustomerSearch();
        $customerSearch->contactPrimaryJoin = new ContactSearchBasic();
        $customerSearch->contactPrimaryJoin->email = $searchField;

        $request = new SearchRequest();
        $request->searchRecord = $customerSearch;

        $netsuiteService = $this->serviceManagement->get();
        $searchResponse = $netsuiteService->search($request);

        if ($searchResponse->searchResult->totalRecords != 0) {
            return $searchResponse->searchResult->recordList->record[0]->internalId;
        }

        return false;
    }

    /**
     * @param CustomerInterface $customer
     * @return CustomerAddressbookList
     * @throws LocalizedException
     * @throws CouldNotSaveException
     */
    public function getAddressBookInNetsuiteFormat(CustomerInterface $customer)
    {
        $defaultBillingAddressId = $customer->getDefaultBilling();
        $defaultShippingAddressId = $customer->getDefaultShipping();

        $addresses = $customer->getAddresses();
        $netsuiteAddressList = new CustomerAddressbookList();
        $netsuiteAddressList->replaceAll = true;

        $defaultCountryId = $this->addressHelper->getAddressDefaultCountryId($addresses);

        foreach ($addresses as $magentoAddress) {
            $netsuiteAddress = new CustomerAddressbook();

            if ($defaultShippingAddressId && $defaultShippingAddressId == $magentoAddress->getId()) {
                $netsuiteAddress->defaultShipping = true;
            } else {
                $netsuiteAddress->defaultShipping = false;
            }

            if ($defaultBillingAddressId && $defaultBillingAddressId == $magentoAddress->getId()) {
                $netsuiteAddress->defaultBilling = true;
            } else {
                $netsuiteAddress->defaultBilling = false;
            }

            $netsuiteAddress->addressbookAddress = new \NetSuite\Classes\Address();

            $netsuiteAddress->addressbookAddress->addressee = $customer->getFirstname() . ' ' .
                $customer->getLastname();
            $netsuiteAddress->addressbookAddress->addrPhone = $this->addressHelper->sanitizePhoneNumber(
                $magentoAddress->getTelephone()
            );
            $addressLine1 = isset($magentoAddress->getStreet()[0]) ? $magentoAddress->getStreet()[0] : '';
            $addressLine2 = isset($magentoAddress->getStreet()[1]) ? $magentoAddress->getStreet()[1] : '';
            $netsuiteAddress->addressbookAddress->addr1 = $addressLine1;
            $netsuiteAddress->addressbookAddress->addr2 = $addressLine2;
            $netsuiteAddress->addressbookAddress->city = $magentoAddress->getCity();
            $netsuiteAddress->addressbookAddress->zip = $magentoAddress->getPostcode();
            $netsuiteAddress->addressbookAddress->state =
                ($magentoAddress instanceof \Magento\Customer\Model\Data\Address) ?
                $magentoAddress->getRegion()->getRegionCode() :
                $magentoAddress->getRegionCode();
            // Daniel: Fix empty country id
            if ($magentoAddress->getCountryId() == '') {
                $magentoAddress->setCountryId($defaultCountryId);
            }
            $country = $this->getCountryInfo($magentoAddress->getCountryId());
            $netsuiteAddress->addressbookAddress->country = $this->transformHelper->transformCountryCode(
                $country->getCountryId()
            );

            $internalIdAttr = $magentoAddress->getCustomAttribute('netsuite_internal_id');
            $addressHash = null;
            if ($internalIdAttr && !empty($internalIdAttr->getValue())) {
                $addressHash = $internalIdAttr->getValue();
            } else {
                // there is no hash generated yet
                $addressHash = md5( // phpcs:ignore
                    $netsuiteAddress->addressbookAddress->addressee .
                    $netsuiteAddress->addressbookAddress->addrPhone .
                    $netsuiteAddress->addressbookAddress->city .
                    $netsuiteAddress->addressbookAddress->zip .
                    $magentoAddress->getId() .
                    time()
                );

                $magentoAddress->setCustomAttribute('netsuite_internal_id', $addressHash);
                if ($magentoAddress instanceof \Magento\Customer\Model\Data\Address) {
                    $this->addressRepository->save($magentoAddress);
                }
            }

            $addressHashField = new StringCustomFieldRef();
            $addressHashField->scriptId = $this->getMagentoAddressFieldId();
            $addressHashField->value = $addressHash;

            $netsuiteAddress->addressbookAddress->customFieldList = new CustomFieldList();
            $netsuiteAddress->addressbookAddress->customFieldList->customField = [$addressHashField];

            $this->eventManager->dispatch(
                'netsuite_address_create_before',
                ['netsuite_address' => $netsuiteAddress->addressbookAddress]
            );

            $netsuiteAddressList->addressbook[] = $netsuiteAddress;
        }

        return $netsuiteAddressList;
    }

    /**
     * @param string $countryId
     * @return Country|null
     */
    protected function getCountryInfo($countryId)
    {
        $countries = $this->getCountriesInfo();
        foreach ($countries as $country) {
            if ($country->getId() == $countryId) {
                return $country;
            }
        }
    }

    /**
     * @return Collection|null
     */
    protected function getCountriesInfo()
    {
        static $countriesCollection = null;
        if (null === $countriesCollection) {
            /** @var  $collection */
            $countriesCollection = $this->countryCollectionFactory->create()->load();
        }
        return $countriesCollection;
    }

    /**
     * @param OrderInterface $order
     * @return mixed
     * @throws Exception
     */
    public function createNetsuiteCustomerFromOrder(OrderInterface $order)
    {
        $existingCustomer = null;

        if ($order->getCustomerId()) {
            try {
                $existingCustomer = $this->customerRepositoryInterface->getById($order->getCustomerId());
            } catch (Exception $e) {// phpcs:ignore
                //$this->netsuiteHelper->log('Customer with ID=[' . $order->getCustomerId() . '] not found');
            }
        } else {
            try {
                $existingCustomer = $this->customerRepositoryInterface->get($order->getCustomerEmail());
            } catch (Exception $e) {// phpcs:ignore
                // just ignore it
            }
        }

        // check if customer with this internalId still exists
        if ($existingCustomer) {
            $internalIdAttr = $existingCustomer->getCustomAttribute('netsuite_internal_id');
            if ($internalIdAttr && $internalIdAttr->getValue()) {
                $netsuiteId = $internalIdAttr->getValue();
                $existing = $this->getByInternalId($netsuiteId);
                if ($existing) {
                    return $internalIdAttr->getValue();
                }
            }
        }

        $internalId = $this->findNetsuiteCustomer('externalIdString', $this->getExternalIdFromOrder($order));
        if ($internalId) {
            if ($existingCustomer && $existingCustomer->getId()) {
                $existingCustomer->setCustomAttribute('netsuite_internal_id', $internalId);
                $this->customerRepositoryInterface->save($existingCustomer);
            }

            return $internalId;
        }

        $internalId = $this->findNetsuiteCustomer('email', $order->getCustomerEmail());
        if ($internalId) {
            if ($existingCustomer && $existingCustomer->getId()) {
                $existingCustomer->setCustomAttribute('netsuite_internal_id', $internalId);
                $this->customerRepositoryInterface->save($existingCustomer);
            }

            return $internalId;
        }

        $internalId = $this->findNetsuiteCustomerByContactEmail($order->getCustomerEmail());
        if ($internalId) {
            if ($existingCustomer && $existingCustomer->getId()) {
                $existingCustomer->setCustomAttribute('netsuite_internal_id', $internalId);
                $this->customerRepositoryInterface->save($existingCustomer);
            }

            return $internalId;
        }

        $customer = $this->customerInterface->create();

        $addresses = $this->getOrderAddresses($order);
        $customerFirstName = $order->getCustomerFirstname();
        $customerLastName = $order->getCustomerLastname();

        $customer->setId(0);
        $customer->setEmail($order->getCustomerEmail());
        $customer->setMiddlename($order->getCustomerMiddlename());

        if (count($addresses)) {
            $customer->setAddresses($addresses);
        }

        $customer->setStoreId($order->getStoreId());
        $customer->setGroupId((int)$order->getCustomerGroupId());
        $customer->setWebsiteId(
            $this->storeManager->getStore($order->getStoreId())->getWebsiteId()
        );

        // try to get names from billing address if they are empty
        if (!$customerFirstName || !$customerLastName) {
            foreach ($addresses as $address) {
                if ($address->getAddressType() === \Magento\Sales\Model\Order\Address::TYPE_BILLING) {
                    $customerFirstName = !$customerFirstName && $address->getFirstname()
                        ? $address->getFirstname() : $customerFirstName;
                    $customerLastName = !$customerLastName && $address->getLastname()
                        ? $address->getLastname() : $customerLastName;
                }
            }
        }
        // if they are still empty - scan throw any other addresses
        if (!$customerFirstName || !$customerLastName) {
            foreach ($addresses as $address) {
                $customerFirstName = !$customerFirstName && $address->getFirstname()
                    ? $address->getFirstname() : $customerFirstName;
                $customerLastName = !$customerLastName && $address->getLastname()
                    ? $address->getLastname() : $customerLastName;
            }
        }

        if (!$customerLastName) {
            $names = explode(' ', $customerFirstName);
            $customerFirstName = $names[0];
            array_shift($names);
            $customerLastName = implode(' ', $names);
        }

        $customer->setFirstname(trim((string)$customerFirstName));
        $customer->setLastname(trim((string)$customerLastName));

        $netsuiteCustomer = $this->getNetsuiteFormat($customer);
        if (empty($netsuiteCustomer->externalId)) {
            $netsuiteCustomer->externalId = null;
        }

        $this->eventManager->dispatch(
            'netsuite_customer_send_before',
            [
                'netsuite_customer' => $netsuiteCustomer,
                'magento_customer' => $customer
            ]
        );

        $request = new AddRequest();
        $request->record = $netsuiteCustomer;
        $response = $this->serviceManagement->get()->add($request);
        if ($response->writeResponse->status->isSuccess) {
            return $response->writeResponse->baseRef->internalId;
        } else {
            // phpcs:ignore
            throw new ConnectorRuntimeException((string)print_r($response->writeResponse->status->statusDetail, true));
        }
    }

    /**
     * @param string $netsuiteInternalId
     * @return null
     * @throws ConnectorRuntimeException
     */
    public function getByInternalId(string $netsuiteInternalId)
    {
        if (!$netsuiteInternalId) {
            return null;
        }

        $cachedCustomer = $this->customerCache[$netsuiteInternalId] ?? null;
        if ($cachedCustomer) {
            return $cachedCustomer;
        }

        $request = new GetRequest();
        $request->baseRef = new RecordRef();
        $request->baseRef->internalId = $netsuiteInternalId;
        $request->baseRef->type = RecordType::customer;

        $getResponse = $this->serviceManagement->get()->get($request);
        if (!$getResponse) {
            throw new ConnectorRuntimeException('NULL response from NS');
        }

        if (!$getResponse->readResponse->status->isSuccess) {
            return null;
        } else {
            return $getResponse->readResponse->record;
        }
    }

    private function getMagentoAddressFieldId()
    {
        return $this->scopeConfig->getValue('mageos_netsuite/customer/magento_address_id_field');
    }

    /**
     * @param $string
     * @return string
     */
    public function limitTo32Chars($string)
    {
        if (strlen($string) > 32) {
            return substr($string, 0, 32);
        }
        return $string;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $magentoOrder
     * @return \Magento\Sales\Api\Data\OrderAddressInterface[]
     */
    public function getOrderAddresses(\Magento\Sales\Api\Data\OrderInterface $magentoOrder)
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilder;

        $filters = [];
        $filters[] = $this->filterBuilder
            ->setField('parent_id')
            ->setConditionType('eq')
            ->setValue($magentoOrder->getEntityId())
            ->create();

        $searchCriteriaBuilder->addFilters($filters);
        $searchCriteria = $searchCriteriaBuilder->create();

        return $this->orderAddressRepository->getList($searchCriteria)->getItems();
    }
}
