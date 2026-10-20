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
 */

namespace MageOS\NetSuiteConnector\Test\Unit\Order\Plugin;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Api\Data\OrderExtension;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use MageOS\NetSuiteConnector\Order\Plugin\AfterOrderRepositoryGet;
use MageOS\NetSuiteConnector\Order\Plugin\BeforeOrderRepositorySave;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Covers MageOS\NetSuiteConnector\Order\Plugin\AfterOrderRepositoryGet: a order read through the repository
 * has its NetSuite fields in the extension attributes, so saving it back keeps them.
 */
// Order's constructor dependencies are mocks created by Magento's ObjectManager helper
#[AllowMockObjectsWithoutExpectations]
class AfterOrderRepositoryGetTest extends TestCase
{
    public function testGetLoadsTheNetSuiteFieldsIntoTheExtensionAttributes(): void
    {
        $order = $this->createOrder();

        $result = (new AfterOrderRepositoryGet())->afterGet($this->repository(), $order);

        $this->assertSame($order, $result);
        $this->assertSame('24769243', $order->getExtensionAttributes()->getNetsuiteInternalId());
        $this->assertSame('2026-10-13 19:00:00', $order->getExtensionAttributes()->getNetsuiteLastImportDate());
    }

    public function testGetListLoadsTheNetSuiteFieldsOfEveryItem(): void
    {
        $first = $this->createOrder();
        $second = $this->createOrder();
        $second->setData('netsuite_internal_id', null);
        $searchResult = $this->createStub(OrderSearchResultInterface::class);
        $searchResult->method('getItems')->willReturn([$first, $second]);

        $result = (new AfterOrderRepositoryGet())->afterGetList($this->repository(), $searchResult);

        $this->assertSame($searchResult, $result);
        $this->assertSame('24769243', $first->getExtensionAttributes()->getNetsuiteInternalId());
        $this->assertNull($second->getExtensionAttributes()->getNetsuiteInternalId());
    }

    public function testSavingWhatGetReturnedKeepsTheNetSuiteId(): void
    {
        $order = $this->createOrder();
        $repository = $this->repository();

        (new AfterOrderRepositoryGet())->afterGet($repository, $order);
        (new BeforeOrderRepositorySave())->beforeSave($repository, $order);

        $this->assertSame('24769243', $order->getData('netsuite_internal_id'));
    }

    private function createOrder(): Order
    {
        /** @var Order $order */
        $order = (new ObjectManager($this))->getObject(Order::class);
        $order->setExtensionAttributes(new OrderExtension());
        $order->setData('netsuite_internal_id', '24769243');
        $order->setData('netsuite_last_import_date', '2026-10-13 19:00:00');

        return $order;
    }

    private function repository(): OrderRepositoryInterface
    {
        return $this->createStub(OrderRepositoryInterface::class);
    }
}
