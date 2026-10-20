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
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use MageOS\NetSuiteConnector\Order\Plugin\BeforeOrderRepositorySave;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Covers MageOS\NetSuiteConnector\Order\Plugin\BeforeOrderRepositorySave: the extension attributes are not
 * loaded with the order, so saving an order through the repository must not clear its NetSuite id.
 */
// Order's constructor dependencies are mocks created by Magento's ObjectManager helper
#[AllowMockObjectsWithoutExpectations]
class BeforeOrderRepositorySaveTest extends TestCase
{
    public function testItKeepsTheNetSuiteIdWhenTheExtensionAttributesDoNotHaveIt(): void
    {
        $order = $this->createOrder();
        $order->setData('netsuite_internal_id', '24769243');
        $order->setData('netsuite_last_import_date', '2026-10-13 19:00:00');

        $this->invoke($order);

        $this->assertSame('24769243', $order->getData('netsuite_internal_id'));
        $this->assertSame('2026-10-13 19:00:00', $order->getData('netsuite_last_import_date'));
    }

    public function testItCopiesTheNetSuiteIdFromTheExtensionAttributes(): void
    {
        $order = $this->createOrder();
        $order->getExtensionAttributes()->setNetsuiteInternalId('24769243');
        $order->getExtensionAttributes()->setNetsuiteLastImportDate('2026-10-13 19:00:00');

        $this->invoke($order);

        $this->assertSame('24769243', $order->getData('netsuite_internal_id'));
        $this->assertSame('2026-10-13 19:00:00', $order->getData('netsuite_last_import_date'));
    }

    private function createOrder(): Order
    {
        /** @var Order $order */
        $order = (new ObjectManager($this))->getObject(Order::class);
        $order->setExtensionAttributes(new OrderExtension());

        return $order;
    }

    private function invoke(Order $order): void
    {
        (new BeforeOrderRepositorySave())->beforeSave($this->createStub(OrderRepositoryInterface::class), $order);
    }
}
