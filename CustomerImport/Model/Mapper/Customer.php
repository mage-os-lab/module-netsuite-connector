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
 */

declare(strict_types=1);

namespace MageOS\NetSuiteConnector\CustomerImport\Model\Mapper;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use NetSuite\Classes\Customer as NetSuiteCustomer;

/**
 * Class Customer - Mapper for Importing Customers
 */
class Customer
{
    private \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerFactory;
    private \Magento\Store\Api\StoreRepositoryInterface $storeRepository;
    private \MageOS\NetSuiteConnector\CustomerImport\Model\Config\CustomerImportConfig $customerImportConfig;
    private \MageOS\NetSuiteConnector\CustomerImport\Model\Mapper\Customer\Address $addressMapping;

    /**
     * Customer constructor.
     * @param \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerFactory
     * @param \Magento\Store\Api\StoreRepositoryInterface $storeRepository
     * @param \MageOS\NetSuiteConnector\CustomerImport\Model\Config\CustomerImportConfig $customerImportConfig
     * @param Customer\Address $addressMapping
     */
    public function __construct(
        \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerFactory,
        \Magento\Store\Api\StoreRepositoryInterface $storeRepository,
        \MageOS\NetSuiteConnector\CustomerImport\Model\Config\CustomerImportConfig $customerImportConfig,
        \MageOS\NetSuiteConnector\CustomerImport\Model\Mapper\Customer\Address $addressMapping
    ) {
        $this->customerFactory = $customerFactory;
        $this->storeRepository = $storeRepository;
        $this->customerImportConfig = $customerImportConfig;
        $this->addressMapping = $addressMapping;
    }

    /**
     * @param NetSuiteCustomer $nsCustomer
     * @param null $magentoCustomer
     * @return CustomerInterface
     * @throws NoSuchEntityException
     */
    public function getMagentoFormat(NetSuiteCustomer $nsCustomer, $magentoCustomer = null): CustomerInterface
    {
        if (null === $magentoCustomer) {
            $magentoCustomer = $this->customerFactory->create();
            $magentoCustomer->setStoreId($this->getStoreId($nsCustomer));
            $magentoCustomer->setGroupId($this->getCustomerGroup($nsCustomer));
            $magentoCustomer->setWebsiteId(
                $this->storeRepository->getById($this->getStoreId($nsCustomer))->getWebsiteId()
            );
        }
        $magentoCustomer->setEmail($nsCustomer->email);
        $magentoCustomer->setFirstname($nsCustomer->firstName);
        $magentoCustomer->setMiddlename($nsCustomer->middleName);
        $magentoCustomer->setLastname($nsCustomer->lastName);

        $magentoCustomer->setCustomAttribute('netsuite_internal_id', $nsCustomer->internalId);
        $magentoCustomer->setAddresses($this->addressMapping->getMagentoFormat($nsCustomer));

        return $magentoCustomer;
    }

    /**
     * Exposing the getter publicly and adding unused parameter to allow easy
     * plugin access and modification if needed.
     *
     * @suppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getStoreId(NetSuiteCustomer $nsCustomer)
    {
        return $this->customerImportConfig->getDefaultStoreId();
    }

    /**
     * Exposing the getter publicly and adding unused parameter to allow easy
     * plugin access and modification if needed.
     *
     * @suppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getCustomerGroup(NetSuiteCustomer $nsCustomer)
    {
        return $this->customerImportConfig->getDefaultCustomerGroup();
    }
}
