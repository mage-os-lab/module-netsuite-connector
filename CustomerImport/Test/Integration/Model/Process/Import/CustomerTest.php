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
 *
 */
// @codingStandardsIgnoreFile
//@SuppressWarnings(PHPMD)
namespace MageOS\NetSuiteConnector\CustomerImport\Test\Integration\Model\Process\Import;


use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use NetSuite\Classes\Address;
use NetSuite\Classes\Customer;
use NetSuite\Classes\CustomerAddressbook;
use NetSuite\Classes\CustomerAddressbookList;

/**
 * TODO: Add tests for UPDATING magento customer.
 * - create dataFixture and create a customer
 * - test 1: use same netsuite_internal_id to be pulled thru that and see that customer gets updated (existing data)
 * - test 2: use same email to be pulled thru and see that customer gets updated (existing data)
 */
class CustomerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;


    protected function setUp():void
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @magentoConfigFixture default/mageos_netsuite/customers_import/default_store_id 1
     * @magentoConfigFixture default/mageos_netsuite/customers_import/default_customer_group 1
     * @magentoDbIsolation enabled
     * @magentoAppArea adminhtml
     */
    public function testProcessNewCustomerWithoutAddressBook()
    {
        $nsCustomer = $this->getNetSuiteCustomer(false, false);

        $customerProcess = $this->objectManager->create(\MageOS\NetSuiteConnector\CustomerImport\Model\Process\Import\Customer::class);
        $customerProcess->process($nsCustomer);

        $customerRepository = $this->objectManager->create(CustomerRepositoryInterface::class);
        $magentoCustomer = $customerRepository->get($nsCustomer->email);

        $this->assertEquals(
            $nsCustomer->internalId,
            $magentoCustomer->getCustomAttribute('netsuite_internal_id')->getValue()
        );
        $this->assertEquals($nsCustomer->firstName, $magentoCustomer->getFirstname());
        $this->assertEquals($nsCustomer->lastName, $magentoCustomer->getLastname());
        $this->assertEquals($nsCustomer->email, $magentoCustomer->getEmail());
    }

    /**
     * @magentoConfigFixture default/mageos_netsuite/customers_import/default_store_id 1
     * @magentoConfigFixture default/mageos_netsuite/customers_import/default_customer_group 1
     * @magentoConfigFixture default/mageos_netsuite/customers_import/default_address_phone 123123123
     * @magentoConfigFixture default/mageos_netsuite/customers_import/default_address_city DefaultCity
     * @magentoConfigFixture default/mageos_netsuite/customers_import/default_address_street DefaultStreet
     * @magentoConfigFixture default/mageos_netsuite/customers_import/default_address_zip 90200
     * @magentoConfigFixture default/mageos_netsuite/customers_import/default_address_country US
     * @magentoDbIsolation enabled
     * @magentoAppArea adminhtml
     */
    public function testProcessNewCustomerWithDefaultValues()
    {
        $nsCustomer = $this->getNetSuiteCustomer(true, true);

        $customerProcess = $this->objectManager->create(\MageOS\NetSuiteConnector\CustomerImport\Model\Process\Import\Customer::class);
        $customerProcess->process($nsCustomer);

        $customerRepository = $this->objectManager->create(CustomerRepositoryInterface::class);
        $magentoCustomer = $customerRepository->get($nsCustomer->email);

        $this->assertEquals(
            $nsCustomer->internalId,
            $magentoCustomer->getCustomAttribute('netsuite_internal_id')->getValue()
        );
        $this->assertEquals($nsCustomer->firstName, $magentoCustomer->getFirstname());
        $this->assertEquals($nsCustomer->lastName, $magentoCustomer->getLastname());
        $this->assertEquals($nsCustomer->email, $magentoCustomer->getEmail());

        $addresses = $magentoCustomer->getAddresses();
        foreach ($addresses as $address) {
            $this->assertEquals('123123123', $address->getTelephone());
            $this->assertEquals('DefaultCity', $address->getCity());
            $this->assertEquals('DefaultStreet', implode('', $address->getStreet()));
            $this->assertEquals('90200', $address->getPostcode());
            $this->assertEquals(true, $address->isDefaultBilling() && $address->isDefaultShipping());
        }
    }

    /**
     * @magentoConfigFixture default/mageos_netsuite/customers_import/default_store_id 1
     * @magentoConfigFixture default/mageos_netsuite/customers_import/default_customer_group 1
     * @magentoDbIsolation enabled
     * @magentoAppArea adminhtml
     */
    public function testProcessNewCustomerWithGivenValues()
    {
        $nsCustomer = $this->getNetSuiteCustomer(true, false);

        $customerProcess = $this->objectManager->create(\MageOS\NetSuiteConnector\CustomerImport\Model\Process\Import\Customer::class);
        $customerProcess->process($nsCustomer);

        $customerRepository = $this->objectManager->create(CustomerRepositoryInterface::class);
        $magentoCustomer = $customerRepository->get($nsCustomer->email);

        $this->assertEquals(
            $nsCustomer->internalId,
            $magentoCustomer->getCustomAttribute('netsuite_internal_id')->getValue()
        );
        $this->assertEquals($nsCustomer->firstName, $magentoCustomer->getFirstname());
        $this->assertEquals($nsCustomer->lastName, $magentoCustomer->getLastname());
        $this->assertEquals($nsCustomer->email, $magentoCustomer->getEmail());

        $addresses = $magentoCustomer->getAddresses();
        foreach ($addresses as $address) {
            $this->assertEquals('2025550124', $address->getTelephone());
            $this->assertEquals('Beverly Hills', $address->getCity());
            $this->assertEquals('Alpine Dr', implode('', $address->getStreet()));
            $this->assertEquals('90210', $address->getPostcode());
            $this->assertEquals('IL', $address->getRegion()->getRegionCode());
            $this->assertEquals(true, $address->isDefaultBilling() && $address->isDefaultShipping());
        }
    }


    private function getNetSuiteCustomer(bool $addressBook, bool $defaultData): Customer
    {
        $customer = new Customer();
        $customer->internalId = 10;
        $customer->isPerson = true;
        $customer->email = 'person@person.com';
        $customer->firstName = 'John';
        $customer->lastName = 'Doe';

        if ($addressBook) {
            $address = new Address();
            $address->internalId = 22;
            if (!$defaultData) {
                $this->populateNetSuiteAddress($address);
            }

            $addressBook = new CustomerAddressbook();
            $addressBook->defaultBilling = 1;
            $addressBook->defaultShipping = 1;
            $addressBook->addressbookAddress = $address;

            $customer->addressbookList = new CustomerAddressbookList();
            $customer->addressbookList->addressbook = [$addressBook];
        }

        return $customer;
    }

    private function populateNetSuiteAddress(Address $address)
    {
        $address->zip = 90210;
        $address->country = \NetSuite\Classes\Country::_unitedStates;
        $address->state = 'IL';
        $address->city = 'Beverly Hills';
        $address->addr1 = 'Alpine Dr';
        $address->addrPhone = '202-555-0124';
    }
}
