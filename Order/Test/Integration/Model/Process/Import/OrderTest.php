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

namespace MageOS\NetSuiteConnector\Order\Test\Integration\Model\Process\Import;

use Magento\Framework\App\Area;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;
use NetSuite\Classes\RecordRef;
use NetSuite\Classes\SalesOrder;
use NetSuite\Classes\SalesOrderItem;
use NetSuite\Classes\SalesOrderItemList;
use MageOS\NetSuiteConnector\Core\Test\Integration\Fixtures\Locator;

/**
 * Class OrderTest -
 * @magentoDbIsolation enabled
 */
class OrderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Path to _files/_files_ns/... folders
     */
    private const RELATIVE_PATH_TO_FIXTURES = '../../../';

    /**
     * {@inheritDoc}
     */
    public static function setUpBeforeClass():void
    {
        $fixturesUsed = [
            '_files/default_rollback.php',
            '_files/product_simple.php',
            '_files/address_data.php',
            '_files/customer.php',
            '_files/order.php',
            '_files/order_set_netsuite_id.php'
        ];

        $path = realpath(__DIR__ . "/" . self::RELATIVE_PATH_TO_FIXTURES) . "/";

        Locator::copy($path, $fixturesUsed);
    }

    /**
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/customer.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/order.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/order_set_netsuite_id.php
     * @magentoConfigFixture default/mageos_netsuite/orders/status_map []
     * @magentoAppIsolation enabled
     */
    public function testOrderImports()
    {
        $objectManager = Bootstrap::getObjectManager();

        Bootstrap::getInstance()
            ->loadArea(Area::AREA_FRONTEND);

        $salesOrder = new SalesOrder();
        $salesOrder->internalId = 1;

        $item = new SalesOrderItem();
        $item->item = new RecordRef();
        $item->item->internalId = 1;
        $item->quantity = 1;
        $item->quantityBilled = 1;
        $item->quantityFulfilled = 1;

        $salesOrder->itemList = new SalesOrderItemList();
        $salesOrder->itemList->item = [$item];

        /** @var \MageOS\NetSuiteConnector\Order\Model\Process\Import\Order $importProcess */
        $importProcess = $objectManager->get(\MageOS\NetSuiteConnector\Order\Model\Process\Import\Order::class);
        $importProcess->process($salesOrder);

        $order = $objectManager->get(Order::class);
        $order->loadByIncrementId('100000001');

        $this->assertEquals('processing', $order->getStatus());

        $this->assertNotEmpty($order->getId());
        $this->assertCount(1, $order->getItems());

        $items = $order->getItems();
        $ids = array_keys($items);
        $firstItem = $items[array_pop($ids)];

        $this->assertEquals(1, $firstItem->getQtyInvoiced());
        $this->assertEquals(1, $firstItem->getQtyShipped());
    }
}
