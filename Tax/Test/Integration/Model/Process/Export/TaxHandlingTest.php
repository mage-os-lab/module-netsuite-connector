<?php
/*
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

namespace MageOS\NetSuiteConnector\Tax\Test\Integration\Model\Process\Export;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use NetSuite\Classes\AddRequest;
use MageOS\NetSuiteConnector\Core\Api\Data\MessageInterface;
use MageOS\NetSuiteConnector\Core\Enum\Message\Queue;
use MageOS\NetSuiteConnector\Core\Test\Integration\Fixtures\Locator;

/**
 * TaxHandlingTest - test for taxes
 * @SuppressWarnings(PHPMD)
 */
class TaxHandlingTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Path to _files/_files_ns/... folders
     */
    const RELATIVE_PATH_TO_FIXTURES = '../../../';

    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    private $objectManager;

    /**
     * @var \MageOS\NetSuiteConnector\Core\Helper\Data|\PHPUnit\Framework\MockObject\MockObject
     */
    private static $nsHelper;

    /**
     * @var \MageOS\NetSuiteConnector\Core\Test\Integration\Helper\NetSuiteServiceFaker
     */
    private static $netsuiteServiceFaker;

    /**
     * @var array
     */
    private $parameters = [
        'by_field' => 'externalIdString',
        'search_success' => 0,
        'netsuite_internal_id' => 2,
        'get_success' => 1,
        'add_success' => 1
    ];

    /**
     * {@inheritDoc}
     */
    public static function setUpBeforeClass():void
    {
        $fixturesUsed = [
            '_files/default_rollback.php',
            '_files/address_data.php',
            '_files/customer_address.php',
            '_files/customer_address_rollback.php',
            '_files/customer.php',
            '_files/customer_rollback.php',
            '_files/order.php',
            '_files/order_rollback.php',
            '_files/submit_order_to_ns_queue.php',
            '_files/submit_order_to_ns_queue_rollback.php',
            '_files/product_simple_taxable.php',
            '_files/product_simple_taxable_rollback.php',
            '_files/quote.php',
            '_files/quote_rollback.php',
        ];

        $path = realpath(__DIR__ . "/" . self::RELATIVE_PATH_TO_FIXTURES) . "/";

        Locator::copy($path, $fixturesUsed);
    }

    /**
     * {@inheritDoc}
     */
    protected function setUp():void
    {
        $this->objectManager = Bootstrap::getObjectManager();

        if (!self::$netsuiteServiceFaker) {
            $path = realpath(__DIR__ . "/" . self::RELATIVE_PATH_TO_FIXTURES) . "/";
            self::$netsuiteServiceFaker = new \MageOS\NetSuiteConnector\Core\Test\Integration\Helper\NetSuiteServiceFaker($path);
        }

        if (!self::$nsHelper) {
            self::$nsHelper = $this->getMockBuilder(\MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management::class)
                ->onlyMethods(['get'])
                ->setConstructorArgs(
                    [
                        $this->objectManager->create(\MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry::class),
                        $this->objectManager->create(\MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig::class),
                        $this->objectManager->create(\MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\ConstructorFactory::class)
                    ]
                )
                ->getMock();
        }

        $this->objectManager->configure([\MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management::class => ['shared' => true]]);
        $this->objectManager->addSharedInstance(self::$nsHelper, \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management::class);
        self::$netsuiteServiceFaker->setParameters($this->parameters);
        $this->setNetSuiteFaker();
    }

    /**
     * Scenario
     * # we have taxable product
     * # we have order with tax and those product
     * # we export order to NS
     * # we use Tax Handling logic that calculate taxes in NS
     * # we check that
     * # - product 1 have isTaxable set
     * # - order have isTaxable set
     * # - custom fields from settings have proper values set (total and tax amounts)
     *
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/product_simple_taxable.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/customer.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/quote.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/order.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/submit_order_to_ns_queue.php
     * @magentoConfigFixture default/mageos_netsuite/payment_methods/netsuite_mapping [{"payment_method":"checkmo","payment_cc":"","internal_netsuite_id":"1"}]
     * @magentoConfigFixture default/mageos_netsuite/shipping_methods/netsuite_default_shipping_id 2
     * @magentoConfigFixture default/mageos_netsuite/shipping_methods/netsuite_mapping {"_1598626367813_813":{"shipping_method":"flatrate_flatrate","shipping_description":"","internal_netsuite_id":"2"}}
     * @magentoConfigFixture default/mageos_netsuite/stock/order_location_id 2
     * @magentoConfigFixture default/mageos_netsuite/tax/tax_logic netsuite_processor
     * @magentoConfigFixture default/mageos_netsuite/tax/sales_order_tax_amount_id custbody_magento_tax_amount
     * @magentoConfigFixture default/mageos_netsuite/tax/sales_order_total_amount_id custbody_magento_total_amount
     * @magentoConfigFixture default/mageos_netsuite/orders/order_skip_discount 1
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testTaxInNetSuiteOrderExport()
    {
        \Magento\TestFramework\Helper\Bootstrap::getInstance()
            ->loadArea(\Magento\Framework\App\Area::AREA_FRONTEND);

        $message = $this->getMessage();
        $orderPlaceProcess = $this->objectManager->create(
            \MageOS\NetSuiteConnector\Order\Model\Process\Export\OrderPlace::class
        );
        $orderPlaceProcess->process($message);

        $orderRepository = $this->objectManager->get(OrderRepositoryInterface::class);
        $order = $orderRepository->get($message->getItemId());

        $nsIdAttributeValue = $order->getNetsuiteInternalId();
        $this->assertEquals($this->parameters['netsuite_internal_id'], $nsIdAttributeValue);

        /** @var AddRequest $addRequest */
        $addRequest = self::$netsuiteServiceFaker->getAddRequest();
        $this->assertNotNull($addRequest);
        $this->assertTrue($addRequest->record->isTaxable);
        $this->assertNotNull($addRequest->record->itemList);
        $this->assertNotNull($addRequest->record->itemList->item[0]);
        $this->assertTrue($addRequest->record->itemList->item[0]->isTaxable);
        $this->assertNotNull($addRequest->record->customFieldList);
        $this->assertEquals($addRequest->record->customFieldList->customField[0]->scriptId, 'custbody_magento_tax_amount');
        $this->assertEquals($addRequest->record->customFieldList->customField[0]->value, 2.25);
        $this->assertEquals($addRequest->record->customFieldList->customField[1]->scriptId, 'custbody_magento_total_amount');
        $this->assertEquals($addRequest->record->customFieldList->customField[1]->value, 100.0);

        $this->cleanUpQueue();
    }


    /**
     * Scenario
     * # we have taxable product
     * # we have order with tax and those product
     * # we export order to NS
     * # we use Tax Handling logic that calculate taxes in as Line Item
     * # we check that
     * # - product 1 have isTaxable set to false
     * # - order have isTaxable set to null
     * # - no custom fields
     * # - line 2 is Tax Line
     *
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/product_simple_taxable.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/customer.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/quote.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/order.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/submit_order_to_ns_queue.php
     * @magentoConfigFixture default/mageos_netsuite/payment_methods/netsuite_mapping [{"payment_method":"checkmo","payment_cc":"","internal_netsuite_id":"1"}]
     * @magentoConfigFixture default/mageos_netsuite/shipping_methods/netsuite_default_shipping_id 2
     * @magentoConfigFixture default/mageos_netsuite/shipping_methods/netsuite_mapping {"_1598626367813_813":{"shipping_method":"flatrate_flatrate","shipping_description":"","internal_netsuite_id":"2"}}
     * @magentoConfigFixture default/mageos_netsuite/stock/order_location_id 2
     * @magentoConfigFixture default/mageos_netsuite/tax/tax_logic tax_item_line
     * @magentoConfigFixture default/mageos_netsuite/tax/tax_item_internal_netsuite_id 9
     * @magentoConfigFixture default/mageos_netsuite/orders/order_skip_discount 1
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testTaxInNetSuiteOrderExportItemTax()
    {
        \Magento\TestFramework\Helper\Bootstrap::getInstance()
            ->loadArea(\Magento\Framework\App\Area::AREA_FRONTEND);

        $message = $this->getMessage();
        $orderPlaceProcess = $this->objectManager->create(
            \MageOS\NetSuiteConnector\Order\Model\Process\Export\OrderPlace::class
        );
        $orderPlaceProcess->process($message);

        $orderRepository = $this->objectManager->get(OrderRepositoryInterface::class);
        $order = $orderRepository->get($message->getItemId());

        $nsIdAttributeValue = $order->getNetsuiteInternalId();
        $this->assertEquals($this->parameters['netsuite_internal_id'], $nsIdAttributeValue);

        /** @var AddRequest $addRequest */
        $addRequest = self::$netsuiteServiceFaker->getAddRequest();
        $this->assertNotNull($addRequest);
        $this->assertNull($addRequest->record->isTaxable);
        $this->assertNotNull($addRequest->record->itemList);
        $this->assertNotNull($addRequest->record->itemList->item[0]);
        $this->assertFalse($addRequest->record->itemList->item[0]->isTaxable);
        $this->assertEquals(2, count($addRequest->record->itemList->item));
        $this->assertEquals(9, $addRequest->record->itemList->item[1]->item->internalId);
        $this->assertEquals(2.25, $addRequest->record->itemList->item[1]->amount);
        $this->assertNull($addRequest->record->customFieldList);

        $this->cleanUpQueue();
    }

    /**
     * Create Message which we process
     *
     * @return MessageInterface
     */
    private function getMessage(): MessageInterface
    {
        // Based on the fixture _files/order.php and as every new order creates with new entity_id,
        // the current message is loaded from messages' collection.
        /** @var \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement */
        $messageManagement = $this->objectManager->create(\MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface::class);
        $messages = $messageManagement->receive(\MageOS\NetSuiteConnector\Core\Enum\Message\Queue::EXPORT(), 50);

        $this->assertCount(1, $messages);

        foreach ($messages as $originalMessage) {
            $message = $originalMessage;
        }

        return $message;
    }

    /**
     * Helper methods for tests
     */
    private function setNetSuiteFaker()
    {
        self::$nsHelper->method('get')
            ->willReturn(self::$netsuiteServiceFaker);
    }

    /**
     * @return void
     */
    private function cleanUpQueue(): void
    {
        /** @var \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement */
        $messageManagement = $this->objectManager->get(\MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface::class);
        $messages = $messageManagement->receive(Queue::EXPORT(), 50);
        foreach ($messages as $message) {
            $messageManagement->deleteById($message->getId());
        }
    }
}
