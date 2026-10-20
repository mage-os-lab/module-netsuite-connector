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

namespace MageOS\NetSuiteConnector\Test\Unit\Shipment\Plugin;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Api\Data\ShipmentExtension;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order\Shipment;
use MageOS\NetSuiteConnector\Shipment\Plugin\BeforeShipmentRepositorySave;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Covers MageOS\NetSuiteConnector\Shipment\Plugin\BeforeShipmentRepositorySave: saving a shipment through the
 * repository must not clear its NetSuite id when the extension attributes do not have it.
 */
// Shipment's constructor dependencies are mocks created by Magento's ObjectManager helper
#[AllowMockObjectsWithoutExpectations]
class BeforeShipmentRepositorySaveTest extends TestCase
{
    public function testItKeepsTheNetSuiteIdWhenTheExtensionAttributesDoNotHaveIt(): void
    {
        $shipment = $this->createShipment();
        $shipment->setData('netsuite_internal_id', '24769243');
        $shipment->setData('netsuite_last_import_date', '2026-10-13 19:00:00');

        $this->invoke($shipment);

        $this->assertSame('24769243', $shipment->getData('netsuite_internal_id'));
        $this->assertSame('2026-10-13 19:00:00', $shipment->getData('netsuite_last_import_date'));
    }

    public function testItCopiesTheNetSuiteIdFromTheExtensionAttributes(): void
    {
        $shipment = $this->createShipment();
        $shipment->getExtensionAttributes()->setNetsuiteInternalId('24769243');
        $shipment->getExtensionAttributes()->setNetsuiteLastImportDate('2026-10-13 19:00:00');

        $this->invoke($shipment);

        $this->assertSame('24769243', $shipment->getData('netsuite_internal_id'));
        $this->assertSame('2026-10-13 19:00:00', $shipment->getData('netsuite_last_import_date'));
    }

    private function createShipment(): Shipment
    {
        /** @var Shipment $shipment */
        $shipment = (new ObjectManager($this))->getObject(Shipment::class);
        $shipment->setExtensionAttributes(new ShipmentExtension());

        return $shipment;
    }

    private function invoke(Shipment $shipment): void
    {
        (new BeforeShipmentRepositorySave())->beforeSave(
            $this->createStub(ShipmentRepositoryInterface::class),
            $shipment
        );
    }
}
