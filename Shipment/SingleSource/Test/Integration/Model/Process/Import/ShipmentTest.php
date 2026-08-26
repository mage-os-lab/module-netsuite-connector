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

// phpcs:disable
namespace MageOS\NetSuiteConnector\Shipment\SingleSource\Test\Integration\Model\Process\Import;

use NetSuite\Classes\ItemFulfillment;
use NetSuite\Classes\ItemFulfillmentItem;
use NetSuite\Classes\ItemFulfillmentItemList;
use NetSuite\Classes\ItemFulfillmentShipStatus;
use MageOS\NetSuiteConnector\Core\Exception\SkipRecordException;
use MageOS\NetSuiteConnector\Core\Test\Integration\Fixtures\Locator;
use Magento\Sales\Model\Order\Shipment;
use Magento\TestFramework\Helper\Bootstrap;
use NetSuite\Classes\Address;
use NetSuite\Classes\Country;
use NetSuite\Classes\RecordRef;
use MageOS\NetSuiteConnector\Core\Test\Integration\Model\NSRecordBuilder;

/**
 * Class ShipmentTest -
 * @SuppressWarnings(PHPMD)
 */
class ShipmentTest extends \PHPUnit\Framework\TestCase
{
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
     * Path to _files/_files_ns/... folders
     */
    const RELATIVE_PATH_TO_FIXTURES = '../../../';

    public static function setUpBeforeClass(): void
    {
        $fixturesUsed = [
            '_files/address_data.php',
            '_files/configurable_attribute.php',
            '_files/customer.php',
            '_files/customer_rollback.php',
            '_files/default_rollback.php',
            '_files/order.php',
            '_files/order_rollback.php',
            '_files/order_set_netsuite_id.php',
            '_files/order_with_configurable.php',
            '_files/order_with_configurable_rollback.php',
            '_files/product_configurable.php',
            '_files/product_configurable_rollback.php',
            '_files/product_simple.php',
            '_files/product_simple_rollback.php',
        ];

        $path = realpath(__DIR__ . "/" . self::RELATIVE_PATH_TO_FIXTURES) . "/";

        Locator::copy($path, $fixturesUsed);
    }

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
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
        $this->objectManager->addSharedInstance(self::$nsHelper,
            \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management::class);
        self::$netsuiteServiceFaker->setParameters($this->parameters);
        $this->setNetSuiteFaker();
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
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testThatItSkipsInvalidRecords()
    {
        $objectManager = Bootstrap::getObjectManager();

        $shipment = NSRecordBuilder::aRecord(ItemFulfillment::class)
            ->withInternalId(1001)
            ->build();

        /** @var \MageOS\NetSuiteConnector\Shipment\Model\Process\Import\Shipment $shipmentImport */
        $shipmentImport = $objectManager->get(\MageOS\NetSuiteConnector\Shipment\Model\Process\Import\Shipment::class);

        $this->assertFalse($shipmentImport->isMagentoImportable($shipment));
        $this->assertFalse($shipmentImport->isAlreadyImported($shipment));

        $this->expectException(SkipRecordException::class);

        $shipmentImport->process($shipment);

        $shipment = NSRecordBuilder::aRecord(ItemFulfillment::class)
            ->withInternalId(1001)
            ->withCreatedFrom(1)
            ->withShipStatus(ItemFulfillmentShipStatus::_shipped)
            ->build();

        $this->expectException(SkipRecordException::class);

        $shipmentImport->process($shipment);
    }

    /**
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/customer.php
     * @magentoDataFixtureBeforeTransaction ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/order.php
     * @magentoDataFixtureBeforeTransaction ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/order_set_netsuite_id.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testThatItImportsTheShipping()
    {
        $objectManager = Bootstrap::getObjectManager();

        $shipment = NSRecordBuilder::aRecord(ItemFulfillment::class)
            ->withInternalId(1001)
            ->withCreatedFrom(1)
            ->withShippingAddress($this->createAddress())
            ->withShipStatus(ItemFulfillmentShipStatus::_shipped)
            ->withEntity(NSRecordBuilder::createRecordRef(1))
            ->withItemList($this->createShipmentItemList())
            ->build();

        /** @var \MageOS\NetSuiteConnector\Shipment\Model\Process\Import\Shipment $shipmentImport */
        $shipmentImport = $objectManager->get(\MageOS\NetSuiteConnector\Shipment\Model\Process\Import\Shipment::class);
        $shipmentRegistry = $objectManager->get(\MageOS\NetSuiteConnector\Shipment\Model\ShipmentRegistry::class);

        $this->assertTrue($shipmentImport->isMagentoImportable($shipment));
        $this->assertFalse($shipmentImport->isAlreadyImported($shipment));

        $shipmentImport->process($shipment);

        /** @var Shipment $shipment */
        $shipment = $shipmentRegistry->getShipmentByNetsuiteId(1001);

        $this->assertNotNull($shipment);
        $this->assertInstanceOf(Shipment::class, $shipment);
        $this->assertEquals('1', $shipment->getStoreId());
        $this->assertCount(1, $shipment->getItems());
        $this->assertEquals(2, $shipment->getTotalQty());
        $this->assertEquals(1, $shipment->getCommentsCollection()->count());

        //
        $items = $shipment->getItems();
        $firstKey = array_keys($items)[0];
        $this->assertEquals(10, $items[$firstKey]->getPrice());
        $this->assertEquals(2, $items[$firstKey]->getQty());
        $this->assertEquals('simple', $items[$firstKey]->getSku());
        $this->assertEquals(1, $items[$firstKey]->getWeight());

        // Address checks
        $shippingAddress = $shipment->getShippingAddress();
        $this->assertEquals('US', $shippingAddress->getCountryId());
        $this->assertEquals(['4th avenue'], $shippingAddress->getStreet());
        $this->assertEquals('NY', $shippingAddress->getRegionCode());
        $this->assertEquals('10001', $shippingAddress->getPostcode());

        $billingAddress = $shipment->getBillingAddress();
        $this->assertEquals('US', $billingAddress->getCountryId());
        $this->assertEquals(['street'], $billingAddress->getStreet());
        $this->assertEquals('CA', $billingAddress->getRegionCode());
        $this->assertEquals('11111', $billingAddress->getPostcode());
    }

    /**
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/customer.php
     * @magentoDataFixtureBeforeTransaction ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/order_with_configurable.php
     * @magentoDataFixtureBeforeTransaction ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/order_set_netsuite_id.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testThatItImportsTheShippingWithConfigurable()
    {
        $objectManager = Bootstrap::getObjectManager();

        $shipment = NSRecordBuilder::aRecord(ItemFulfillment::class)
            ->withInternalId(1001)
            ->withCreatedFrom(1)
            ->withShippingAddress($this->createAddress())
            ->withShipStatus(ItemFulfillmentShipStatus::_shipped)
            // customer id
            ->withEntity(NSRecordBuilder::createRecordRef(1))
            ->withItemList($this->createShipmentItemListConfigurable())
            ->build();

        /** @var \MageOS\NetSuiteConnector\Shipment\Model\Process\Import\Shipment $shipmentImport */
        $shipmentImport = $objectManager->get(\MageOS\NetSuiteConnector\Shipment\Model\Process\Import\Shipment::class);
        $shipmentRegistry = $objectManager->get(\MageOS\NetSuiteConnector\Shipment\Model\ShipmentRegistry::class);

        $this->assertTrue($shipmentImport->isMagentoImportable($shipment));
        $this->assertFalse($shipmentImport->isAlreadyImported($shipment));

        $shipmentImport->process($shipment);

        /** @var Shipment $shipment */
        $shipment = $shipmentRegistry->getShipmentByNetsuiteId(1001);

        $this->assertNotNull($shipment);
        $this->assertInstanceOf(Shipment::class, $shipment);
        $this->assertEquals('1', $shipment->getStoreId());
        $this->assertCount(1, $shipment->getItems());
        $this->assertEquals(2, $shipment->getTotalQty());
        $this->assertEquals(1, $shipment->getCommentsCollection()->count());

        //
        $items = $shipment->getItems();
        $firstKey = array_keys($items)[0];

        // configurable
        $this->assertEquals(10, $items[$firstKey]->getPrice());
        $this->assertEquals(2, $items[$firstKey]->getQty());
        $this->assertEquals(1, $items[$firstKey]->getWeight());

        // Address checks
        $shippingAddress = $shipment->getShippingAddress();
        $this->assertEquals('US', $shippingAddress->getCountryId());
        $this->assertEquals(['4th avenue'], $shippingAddress->getStreet());
        $this->assertEquals('NY', $shippingAddress->getRegionCode());
        $this->assertEquals('10001', $shippingAddress->getPostcode());

        $billingAddress = $shipment->getBillingAddress();
        $this->assertEquals('US', $billingAddress->getCountryId());
        $this->assertEquals(['street'], $billingAddress->getStreet());
        $this->assertEquals('CA', $billingAddress->getRegionCode());
        $this->assertEquals('11111', $billingAddress->getPostcode());
    }

    /**
     * @return Address
     */
    private function createAddress(): Address
    {
        $address = new Address();
        $address->country = Country::_unitedStates;
        $address->state = 'NY';
        $address->zip = '10001';
        $address->addr1 = '4th avenue';
        return $address;
    }

    /**
     * @return ItemFulfillmentItemList
     */
    private function createShipmentItemList(): ItemFulfillmentItemList
    {
        $item1 = new ItemFulfillmentItem();
        $item1->item = NSRecordBuilder::createRecordRef(1);
        $item1->quantity = 2;

        $itemList = new ItemFulfillmentItemList();
        $itemList->item = [
            $item1
        ];

        return $itemList;
    }

    /**
     * @return ItemFulfillmentItemList
     */
    private function createShipmentItemListConfigurable(): ItemFulfillmentItemList
    {
        $item2 = new ItemFulfillmentItem();
        $item2->item = NSRecordBuilder::createRecordRef(10);
        $item2->quantity = 2;

        $itemList = new ItemFulfillmentItemList();
        $itemList->item = [
            $item2
        ];

        return $itemList;
    }
}
