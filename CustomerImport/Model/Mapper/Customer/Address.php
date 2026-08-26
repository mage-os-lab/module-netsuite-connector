<?php declare(strict_types=1);
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

namespace MageOS\NetSuiteConnector\CustomerImport\Model\Mapper\Customer;

use NetSuite\Classes\Address as NetSuiteAddress;
use NetSuite\Classes\Customer as NetSuiteCustomer;
use MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException;

/**
 * Class CustomerAddress - mapper for Importing Customers' Address
 */
class Address
{
    private \MageOS\NetSuiteConnector\CustomerImport\Model\Config\CustomerImportConfig $customerImportConfig;
    private \MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger;
    private \MageOS\NetSuiteConnector\CustomerImport\Model\Mapper\Customer\Address\Map $addressMap;
    private \Magento\Customer\Api\Data\AddressInterfaceFactory $addressFactory;

    /**
     * Address constructor.
     * @param \Magento\Customer\Api\Data\AddressInterfaceFactory $addressFactory
     * @param \MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger
     * @param \MageOS\NetSuiteConnector\CustomerImport\Model\Config\CustomerImportConfig $customerImportConfig
     * @param Address\Map $addressMap
     */
    public function __construct(
        \Magento\Customer\Api\Data\AddressInterfaceFactory $addressFactory,
        \MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger,
        \MageOS\NetSuiteConnector\CustomerImport\Model\Config\CustomerImportConfig $customerImportConfig,
        \MageOS\NetSuiteConnector\CustomerImport\Model\Mapper\Customer\Address\Map $addressMap
    ) {
        $this->customerImportConfig = $customerImportConfig;
        $this->logger = $logger;
        $this->addressMap = $addressMap;
        $this->addressFactory = $addressFactory;
    }

    /**
     * TODO: Research this more. What happens with existing addresses?
     *
     * @param NetSuiteCustomer $nsCustomer
     * @return \Magento\Customer\Api\Data\AddressInterface[]
     */
    public function getMagentoFormat(NetSuiteCustomer $nsCustomer): array
    {
        $result = [];
        if (empty($nsCustomer->addressbookList) || empty($nsAddressBooks = $nsCustomer->addressbookList->addressbook)) {
            return $result;
        }

        foreach ($nsAddressBooks as $nsAddressBook) {
            $nsAddress = $nsAddressBook->addressbookAddress;

            try {
                $this->validate($nsAddress);
            } catch (DataIntegrityException $e) {
                $this->logger->debug(sprintf(
                    'Skipping address import (ID: %s) for NS Customer ID: %s (Fields: %s) ',
                    $nsAddress->internalId,
                    $nsCustomer->internalId,
                    implode(', ', $e->getMessage())
                ));

                // Skip to next AddressBook as this one doesn't have all the fields Required for the import
                continue;
            }

            $magentoAddress = $this->addressFactory->create();

            //main information about default address role
            $magentoAddress->setIsDefaultShipping($nsAddressBook->defaultShipping);
            $magentoAddress->setIsDefaultBilling($nsAddressBook->defaultBilling);

            //personal information
            $magentoAddress->setLastname($nsCustomer->lastName);
            $magentoAddress->setFirstname($nsCustomer->firstName);
            $magentoAddress->setMiddlename($nsCustomer->middleName);

            // TODO: Verify this works - IT DOES NOT!
            $magentoAddress->setData('netsuite_internal_id', $nsAddress->internalId);

            $this->addressMap->mapNetSuiteToMagento($nsAddress, $magentoAddress);

            $result[] = $magentoAddress;
        }

        return $result;
    }

    /**
     * @param NetSuiteAddress $nsAddress
     * @throws DataIntegrityException
     */
    private function validate(NetSuiteAddress $nsAddress):void
    {
        $requiredAddressFields = (array)$this->customerImportConfig->getRequiredAddressFields();

        $missingFields = [];
        foreach ($requiredAddressFields as $requiredAddressField) {
            if (isset($nsAddress->$requiredAddressField) && $nsAddress->$requiredAddressField === null) {
                $missingFields[] = $requiredAddressField;
            }
        }

        if (!empty($missingFields)) {
            throw new DataIntegrityException(implode(', ', $missingFields));
        }
    }
}
