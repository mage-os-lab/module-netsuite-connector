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

namespace MageOS\NetSuiteConnector\Customer\Test\Integration\Model\Process\Export;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use NetSuite\Classes\AddRequest;
use MageOS\NetSuiteConnector\Core\Api\Data\MessageInterface;
use MageOS\NetSuiteConnector\Core\Enum\Message\Queue;
use MageOS\NetSuiteConnector\Core\Test\Integration\Fixtures\Locator;

// @codingStandardsIgnoreStart
/**
 * Class CustomerSaveTest -
 * @SuppressWarnings(PHPMD)
 */
class CustomerSaveTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management|\PHPUnit\Framework\MockObject\MockObject
     */
    private static $nsHelper;

    /**
     * @var \MageOS\NetSuiteConnector\Core\Test\Integration\Helper\NetSuiteServiceFaker
     */
    private static $netsuiteServiceFaker;

    /**
     * Path to _files/_files_ns/... folders
     */
    const RELATIVE_PATH_TO_FIXTURES = '../../../';

    public static function setUpBeforeClass():void
    {
        $fixturesUsed = [
            '_files/customer.php',
            '_files/customer_address.php'
        ];

        $path = realpath(__DIR__ . "/" . self::RELATIVE_PATH_TO_FIXTURES) . "/";

        Locator::copy($path, $fixturesUsed);
    }

    /**
     * $netusiteServicerFaker is a replacement class for WSDL Netsuite class
     * $nsHelper is a mock because we use getNetSuiteService() call to get access to WSDL Netsuite class
     *
     * Because how Magento & phpunit works, we need to have them as static values. Main reason - the Mock is
     * cached but on the second test we create a new instance of the Mock but the old one is actually still active
     * in Magento code.
     */
    protected function setUp():void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->objectManager = $objectManager;

        if (!self::$netsuiteServiceFaker) {
            $path = realpath(__DIR__ . "/" . self::RELATIVE_PATH_TO_FIXTURES) . "/";
            self::$netsuiteServiceFaker = new \MageOS\NetSuiteConnector\Core\Test\Integration\Helper\NetSuiteServiceFaker($path);
        }

        if (!self::$nsHelper) {
            self::$nsHelper = $this->getMockBuilder(\MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management::class)
                ->onlyMethods(['get'])
                ->disableOriginalConstructor()
                ->getMock();
        }

        $this->objectManager->configure([\MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management::class => ['shared' => true]]);
        $this->objectManager->addSharedInstance(self::$nsHelper, \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management::class);

    }

    /**
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/customer.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/customer_address.php
     * @magentoConfigFixture default/mageos_netsuite/products/price_level_map  {"_1577106127149_149":{"customer_group":"1","price_level":"5"}}
     * @magentoDbIsolation enabled
     */
    public function testProcessNewCustomer()
    {
        $parameters = [
            'by_field' => 'externalIdString',
            'search_success' => 0,
            'netsuite_internal_id' => 11,
            'add_success' => 1
        ];
        self::$netsuiteServiceFaker->setParameters($parameters);
        $this->setNetSuiteServiceFaker();

        $message = $this->getMessage();

        $customerSaveProcess = $this->objectManager->create(\MageOS\NetSuiteConnector\Customer\Model\Process\Export\CustomerSave::class);
        $customerSaveProcess->process($message);

        // fetch the customer again, it should have a different internal_id
        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $customer = $customerRepository->getById(1);

        $nsIdAttribute = $customer->getCustomAttribute('netsuite_internal_id');
        $this->assertEquals($parameters['netsuite_internal_id'], $nsIdAttribute->getValue());

        /** @var AddRequest $addRequest */
        $addRequest = self::$netsuiteServiceFaker->getAddRequest();
        $expectedAddRequest = $this->getRequest('Customer-new');
        // Using time() in the code, should be refactored
        unset(
            $addRequest->record->addressbookList->addressbook[0]->addressbookAddress->customFieldList->customField[0],
            $expectedAddRequest->record->addressbookList->addressbook[0]->addressbookAddress->customFieldList->customField[0]
        );
        $this->assertEquals($expectedAddRequest, $addRequest);
    }

    /**
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/customer.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/customer_address.php
     * @magentoConfigFixture default/mageos_netsuite/products/price_level_map  {"_1577106127149_149":{"customer_group":"1","price_level":"5"}}
     * @magentoDbIsolation enabled
     */
    public function testProcessAddDuplicateCustomer()
    {
        $parameters = [
            'by_field' => 'externalIdString',
            'search_success' => 0,
            'netsuite_internal_id' => 2,
            'add_success' => 'dup',
            'update_success' => 1
        ];
        self::$netsuiteServiceFaker->setParameters($parameters);
        $this->setNetSuiteServiceFaker();

        $message = $this->getMessage();

        $customerSaveProcess = $this->objectManager->create(\MageOS\NetSuiteConnector\Customer\Model\Process\Export\CustomerSave::class);
        $customerSaveProcess->process($message);

        // fetch the customer again, it needs to have same internal_id
        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $customer = $customerRepository->getById(1);

        $nsIdAttribute = $customer->getCustomAttribute('netsuite_internal_id');
        $this->assertEquals($parameters['netsuite_internal_id'], $nsIdAttribute->getValue());

        $addRequest = self::$netsuiteServiceFaker->getAddRequest();
        $expectedAddRequest = $this->getRequest('Customer-Duplicate-new');// Using time() in the code, should be refactored
        unset(
            $addRequest->record->addressbookList->addressbook[0]->addressbookAddress->customFieldList->customField[0],
            $expectedAddRequest->record->addressbookList->addressbook[0]->addressbookAddress->customFieldList->customField[0]
        );
        $addRequest->record->entityId = null;
        $this->assertEquals($expectedAddRequest, $addRequest);

        $updateRequest = self::$netsuiteServiceFaker->getUpdateRequest();
        $expectedUpdateRequest = $this->getRequest('Customer-Duplicate-update');
        $this->assertEquals($expectedUpdateRequest, $updateRequest);
    }

    /**
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/customer.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/customer_address.php
     * @magentoConfigFixture default/mageos_netsuite/products/price_level_map  {"_1577106127149_149":{"customer_group":"1","price_level":"5"}}
     * @magentoDbIsolation enabled
     */
    public function testProcessUpdateCustomer()
    {
        $parameters = [
            'by_field' => 'externalIdString',
            'search_success' => 1,
            'netsuite_internal_id' => 12,
            'update_success' => 1
        ];
        self::$netsuiteServiceFaker->setParameters($parameters);
        $this->setNetSuiteServiceFaker();

        $message = $this->getMessage();

        $customerSaveProcess = $this->objectManager->create(\MageOS\NetSuiteConnector\Customer\Model\Process\Export\CustomerSave::class);
        $customerSaveProcess->process($message);

        // fetch the customer again, it needs to have same internal_id
        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $customer = $customerRepository->getById(1);

        $nsIdAttribute = $customer->getCustomAttribute('netsuite_internal_id');
        $this->assertEquals($parameters['netsuite_internal_id'], $nsIdAttribute->getValue());

        $updateRequest = self::$netsuiteServiceFaker->getUpdateRequest();
        $expectedUpdateRequest = $this->getRequest('Customer-update');
        unset(
            $updateRequest->record->addressbookList->addressbook[0]->addressbookAddress->customFieldList->customField[0],
            $expectedUpdateRequest->record->addressbookList->addressbook[0]->addressbookAddress->customFieldList->customField[0]
        );
        $updateRequest->record->entityId = null;
        $this->assertEquals($expectedUpdateRequest, $updateRequest);
    }

    /*
     * Helper methods for tests
     */

    private function setNetSuiteServiceFaker()
    {
        self::$nsHelper->method('get')
            ->willReturn(self::$netsuiteServiceFaker);
    }

    /**
     * Create Message which we process
     *
     * @return MessageInterface
     */
    private function getMessage(): MessageInterface
    {
        // based on magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/customer.php
        $createdCustomerId = 1;

        /** @var \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManager */
        $messageManager = $this->objectManager->create(\MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface::class);
        $message = $messageManager->createMessage(
            \MageOS\NetSuiteConnector\Customer\Model\Process\Export\CustomerSave::MESSAGE_ACTION,
            $createdCustomerId,
            Queue::EXPORT()
        );

        return $message;
    }

    /**
     * Fetch expected request from file for comparison
     *
     * @param $fileName
     * @return mixed
     */
    private function getRequest(string $fileName)
    {
        $file = __DIR__ . "/../../../_files_ns_request/" . $fileName;
        return unserialize(rtrim(file_get_contents($file)));
    }
}
