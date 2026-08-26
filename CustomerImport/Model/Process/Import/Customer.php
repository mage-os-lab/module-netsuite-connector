<?php declare(strict_types=1);
/*
 *   RocketWeb
 *
 *  NOTICE OF LICENSE
 *
 *  This source file is subject to the Open Software License (OSL 3.0)
 *  that is bundled with this package in the file LICENSE.txt.
 *  It is also available through the world-wide-web at this URL:
 *  http://opensource.org/licenses/osl-3.0.php
 *
 *  @category  RocketWeb
 *  @package   MageOS_NetSuiteConnector
 *  @copyright Copyright (c) 2026 RocketWeb (http://rocketweb.com)
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  @author    Rocket Web Inc.
 *
 *
 */

namespace MageOS\NetSuiteConnector\CustomerImport\Model\Process\Import;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use NetSuite\Classes\CustomerSearchBasic;
use NetSuite\Classes\Record;
use NetSuite\Classes\RecordType;
use NetSuite\Classes\SearchDateField;
use NetSuite\Classes\SearchDateFieldOperator;
use NetSuite\Classes\SearchRequest;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\CompareDate;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\ConvertDate;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\CustomFieldAccess;
use MageOS\NetSuiteConnector\Core\Model\Process\Import\AbstractImportProcessor;
use MageOS\NetSuiteConnector\CustomerImport\Model\ConfigProvider\Permissions as Permissions;

/**
 * Imports Customers (isPerson=true)
 *
 * The phpmd is reporting coupling of 25, primarily because of \NetSuite\Classes, so ignoring it
 * @suppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Customer extends AbstractImportProcessor
{
    public const MESSAGE_ACTION = 'customer';

    private \MageOS\NetSuiteConnector\CustomerImport\Model\ConfigProvider\Permissions $permissions;
    private \MageOS\NetSuiteConnector\CustomerImport\Model\MagentoCustomerRepository $magentoCustomerRepository;
    private \MageOS\NetSuiteConnector\CustomerImport\Model\Mapper\Customer $customerMapper;
    private \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository;
    private \MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry $registry;
    private \MageOS\NetSuiteConnector\CustomerImport\Model\Config\CustomerImportConfig $customerImportConfig;

    /**
     * Customer constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry $registry
     * @param Permissions $permissions
     * @param \MageOS\NetSuiteConnector\CustomerImport\Model\MagentoCustomerRepository $magentoCustomerRepository
     * @param \MageOS\NetSuiteConnector\CustomerImport\Model\Mapper\Customer $customerMapper
     * @param \MageOS\NetSuiteConnector\CustomerImport\Model\Config\CustomerImportConfig $customerImportConfig
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry $registry,
        \MageOS\NetSuiteConnector\CustomerImport\Model\ConfigProvider\Permissions $permissions,
        \MageOS\NetSuiteConnector\CustomerImport\Model\MagentoCustomerRepository $magentoCustomerRepository,
        \MageOS\NetSuiteConnector\CustomerImport\Model\Mapper\Customer $customerMapper,
        \MageOS\NetSuiteConnector\CustomerImport\Model\Config\CustomerImportConfig $customerImportConfig
    ) {
        $permissionHelper = $permissions;
        parent::__construct($permissionHelper, $context, $serviceManagement);
        $this->permissions = $permissions;
        $this->magentoCustomerRepository = $magentoCustomerRepository;
        $this->customerMapper = $customerMapper;
        $this->customerRepository = $customerRepository;
        $this->registry = $registry;
        $this->customerImportConfig = $customerImportConfig;
    }

    /**
     * @return mixed
     */
    public function getPermissionName(): string
    {
        return Permissions::IMPORT_CUSTOMER;
    }

    /**
     * this model import only individual customers. so we omit all customers-companies
     * for companies there should be separate import model
     * also we check Export to Magento field (script-id is configured in Store Admin) if it is not set - we ignore
     * this check
     * @param Record $customer
     * @return bool
     */
    public function isMagentoImportable(Record $customer): bool
    {
        if (null === $customer->isPerson || false === $customer->isPerson || null === $customer->email) {
            return false;
        }
        $customField = $this->customerImportConfig->getIsImportableFieldId();
        if (!empty($customField)) {
            return (bool)CustomFieldAccess::get($customer, (string)$customField);
        }
        return true;
    }

    /**
     * @param Record $record
     * @return bool
     * @throws LocalizedException
     */
    public function isAlreadyImported(Record $record): bool
    {
        /**
         * we check the customer exist by NS internal id and email
         *
         */
        $magentoCustomer = $this->getMagentoCustomer($record);
        if ($magentoCustomer === null) {
            return false;
        }
        /**
         * we need to update customer entity if its updated_at is less then in the NS.
         * to avoid circular updates (import/export) we do not trigger export during import save.
         *
         * Return needs to be inverted (shouldUpdate => true, isAlreadyImported needs to be FALSE!)
         */
        $netsuiteUpdateDatetime = ConvertDate::fromNetSuiteToSql($record->lastModifiedDate);
        return !CompareDate::shouldUpdate($magentoCustomer->getUpdatedAt(), $netsuiteUpdateDatetime);
    }

    /**
     * @return string
     */
    public function getRecordType(): string
    {
        return RecordType::customer;
    }

    /**
     * @return string
     */
    public function getMessageType(): string
    {
        return self::MESSAGE_ACTION;
    }

    /**
     * @return bool
     * @throws \MageOS\NetSuiteConnector\Core\Exception\ConfigurationException
     */
    public function isActive(): bool
    {
        return $this->permissions->isFeatureEnabled($this->getPermissionName());
    }

    /**
     * @param Record $record
     * @return CustomerInterface|null
     * @throws LocalizedException
     */
    private function getMagentoCustomer(Record $record): ?CustomerInterface
    {
        $customer = $this->magentoCustomerRepository->getCustomerByField(
            'netsuite_internal_id',
            $record->internalId
        );
        if ($customer !== null) {
            return $customer;
        }

        /**
         * The method returns Customer OR null so no need for extra IF
         */
        return $this->magentoCustomerRepository->getCustomerByField(
            'email',
            $record->email
        );
    }

    /**
     * @param Record|\NetSuite\Classes\Customer $customer
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\State\InputMismatchException
     */
    public function process(Record $customer): void
    {
        $this->eventManager->dispatch('netsuite_customer_import_before', ['netsuite_item' => $customer]);
        $magentoExistingCustomer = $this->getMagentoCustomer($customer);
        $magentoCustomer = $this->customerMapper->getMagentoFormat($customer, $magentoExistingCustomer);

        /**
         * this flag helps us to ignore the exporting to NS during customer entity update - to avoid circular updates
         */
        $this->registry->register('netsuite_skip_customer_export', true, true);

        if ($magentoExistingCustomer === null) {
            /**
             * We have a new customer import, so we should set imported_pwd_not_set
             */
            $magentoCustomer->setCustomAttribute('imported_pwd_not_set', true);
        }

        $magentoCustomer = $this->customerRepository->save($magentoCustomer);

        $this->eventManager->dispatch(
            'netsuite_customer_import_after',
            ['netsuite_customer' => $customer, 'magento_customer' => $magentoCustomer]
        );
    }

    /**
     * @param $recordType
     * @param string $startDateTime
     * @return SearchRequest
     * @throws \MageOS\NetSuiteConnector\Core\Exception\NetSuiteRuntimeException
     *
     * @suppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getNetsuiteRequest($recordType, string $startDateTime): SearchRequest
    {
        $now = new \DateTime($this->serviceManagement->getServerTime());

        $searchDateField = new SearchDateField();
        $searchDateField->searchValue = $startDateTime;
        $searchDateField->searchValue2 = $now->format(\DateTime::ISO8601);
        $searchDateField->operator = SearchDateFieldOperator::within;

        $customerSearchBasic = new CustomerSearchBasic();
        $customerSearchBasic->lastModifiedDate = $searchDateField;
        $customerSearchBasic->isPerson = new \NetSuite\Classes\SearchBooleanField();
        $customerSearchBasic->isPerson->searchValue = true;

        $this->eventManager->dispatch(
            'netsuite_import_request_before',
            [
                'record_type' => $this->getRecordType(),
                'search_object' => $customerSearchBasic
            ]
        );

        $searchRequest = new SearchRequest();
        $searchRequest->searchRecord = $customerSearchBasic;

        return $searchRequest;
    }
}
