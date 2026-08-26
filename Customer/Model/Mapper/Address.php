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

use Exception;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Directory\Api\CountryInformationAcquirerInterfaceFactory;
use Magento\Directory\Model\Country;
use Magento\Directory\Model\ResourceModel\Country\Collection;
use Magento\Directory\Model\ResourceModel\Country\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\Context;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderAddressInterfaceFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderAddressRepositoryInterface;
use MageOS\NetSuiteConnector\Core\Helper\Transform;

/**
 * Class Address
 */
class Address
{
    public const DEFAULT_COUNTY_ID = 'US';
    /**
     * @var CountryInformationAcquirerInterfaceFactory
     */
    protected $countryInformationAcquirerInterfaceFactory;

    /**
     * @var Transform
     */
    protected $transformHelper;

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @var OrderAddressInterfaceFactory
     */
    protected $orderAddressInterface;

    /**
     * @var OrderAddressRepositoryInterface
     */
    protected $orderAddressRepositoryInterface;

    /**
     * @var CollectionFactory
     */
    private $countryCollectionFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param CountryInformationAcquirerInterfaceFactory $countryInformationAcquirerInterfaceFactory
     * @param Transform $transformHelper
     * @param Context $context
     * @param OrderAddressInterfaceFactory $orderAddressInterface
     * @param OrderAddressRepositoryInterface $orderAddressRepositoryInterface
     * @param CollectionFactory $countryCollectionFactory
     */
    public function __construct(
        CountryInformationAcquirerInterfaceFactory $countryInformationAcquirerInterfaceFactory,
        Transform $transformHelper,
        Context $context,
        OrderAddressInterfaceFactory $orderAddressInterface,
        OrderAddressRepositoryInterface $orderAddressRepositoryInterface,
        CollectionFactory $countryCollectionFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->countryInformationAcquirerInterfaceFactory = $countryInformationAcquirerInterfaceFactory;
        $this->transformHelper = $transformHelper;
        $this->eventManager = $context->getEventDispatcher();
        $this->orderAddressInterface = $orderAddressInterface;
        $this->orderAddressRepositoryInterface = $orderAddressRepositoryInterface;
        $this->countryCollectionFactory = $countryCollectionFactory;
        $this->scopeConfig = $scopeConfig;
    }

    public function getAddressNetsuiteFormatFromOrderAddress(OrderAddressInterface $address)
    {
        $netsuiteAddress = new \NetSuite\Classes\Address();
        $netsuiteAddress->addr1 = isset($address->getStreet()[0]) ? $address->getStreet()[0] : '';
        $netsuiteAddress->addr2 = isset($address->getStreet()[1]) ? $address->getStreet()[1] : '';
        $netsuiteAddress->city = $address->getCity();
        $country = $this->getCountryInfo($address->getCountryId());
        $netsuiteAddress->country = $this->transformHelper->transformCountryCode($country->getCountryId());
        $netsuiteAddress->addressee = $address->getFirstname() . ' ' . $address->getLastname();
        $netsuiteAddress->addrPhone = $this->sanitizePhoneNumber($address->getTelephone());
        $netsuiteAddress->state = $address->getRegionCode();
        $netsuiteAddress->zip = $address->getPostcode();

        $this->eventManager->dispatch('netsuite_address_create_before', ['netsuite_address' => $netsuiteAddress]);
        return $netsuiteAddress;
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

        // Return default country to avoid exceptions
        foreach ($countries as $country) {
            if ($country->getId() == self::DEFAULT_COUNTY_ID) {
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
        if ($countriesCollection === null) {
            /** @var  $collection */
            $countriesCollection = $this->countryCollectionFactory->create()->load();
        }
        return $countriesCollection;
    }

    public function getAddressMagentoFormatFromNetsuiteAddress(
        $netsuiteAddress,
        $netsuiteCustomer = null,
        ?OrderInterface $magentoOrder = null
    ) {
        /** @var \Address $netsuiteAddress */
        /** @var Customer $netsuiteCustomer */

        $magentoAddress = $this->orderAddressInterface->create();
        $countryCode = $this->transformHelper->netsuiteCountryToCountryCode($netsuiteAddress->country);

        if ($magentoOrder) {
            $magentoAddress->setOrder($magentoOrder);
            $magentoAddress->setCustomerId($magentoOrder->getCustomerId());
        }

        $magentoAddress->setRegion($netsuiteAddress->state);
        $magentoAddress->setPostcode($netsuiteAddress->zip);
        $magentoAddress->setCountryId($countryCode);

        $address = [$netsuiteAddress->addr1];
        if ($netsuiteAddress->addr2) {
            $address[] = $netsuiteAddress->addr2;
        }
        if ($netsuiteAddress->addr3) {
            $address[] = $netsuiteAddress->addr3;
        }
        $magentoAddress->setStreet($address);

        $magentoAddress->setCity($netsuiteAddress->city);

        $magentoAddress->setTelephone($netsuiteAddress->addrPhone);

        if ($netsuiteCustomer) {
            $magentoAddress->setLastname($netsuiteCustomer->lastName);
            $magentoAddress->setFirstname($netsuiteCustomer->firstName);
            $magentoAddress->setEmail($netsuiteCustomer->email);
            $magentoAddress->setFax($netsuiteCustomer->fax);
            $magentoAddress->setMiddlename($netsuiteCustomer->middleName);
            $magentoAddress->setCompany($netsuiteCustomer->companyName);
            if (empty($magentoAddress->getTelephone())) {
                $magentoAddress->setTelephone($netsuiteCustomer->phone);
            }
        }

        return $magentoAddress;
    }

    public function sanitizePhoneNumber($phoneNumber)
    {
        $phoneNumber = preg_replace('/[^\d]/', '', trim((string)$phoneNumber));

        if (strlen($phoneNumber) < 7) {
            $phoneNumber = '';
        }

        return $phoneNumber;
    }

    /**
     * Get address default country id
     * @note Fix to address with empty country_id
     * @param AddressInterface[]|null $addresses
     * @return string
     * @author Daniel
     *
     */
    public function getAddressDefaultCountryId($addresses)
    {
        $countryId = self::DEFAULT_COUNTY_ID;
        if (!empty($addresses)) {
            try {
                foreach ($addresses as $magentoAddress) {
                    if ($magentoAddress->getCountryId() != '') {
                        $countryId = $magentoAddress->getCountryId();
                        break;
                    }
                }
            } catch (Exception $e) {// phpcs:ignore
                // expected to fail if country_id is empty
            }
        }
        return $countryId;
    }
}
