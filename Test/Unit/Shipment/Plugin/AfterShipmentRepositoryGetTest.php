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
use Magento\Sales\Api\Data\ShipmentSearchResultInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order\Shipment;
use MageOS\NetSuiteConnector\Shipment\Plugin\AfterShipmentRepositoryGet;
use MageOS\NetSuiteConnector\Shipment\Plugin\BeforeShipmentRepositorySave;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Covers MageOS\NetSuiteConnector\Shipment\Plugin\AfterShipmentRepositoryGet: a shipment read through the repository
 * has its NetSuite fields in the extension attributes, so saving it back keeps them.
 */
// Shipment's constructor dependencies are mocks created by Magento's ObjectManager helper
#[AllowMockObjectsWithoutExpectations]
class AfterShipmentRepositoryGetTest extends TestCase
{
    public function testGetLoadsTheNetSuiteFieldsIntoTheExtensionAttributes(): void
    {
        $shipment = $this->createShipment();

        $result = (new AfterShipmentRepositoryGet())->afterGet($this->repository(), $shipment);

        $this->assertSame($shipment, $result);
        $this->assertSame('24769243', $shipment->getExtensionAttributes()->getNetsuiteInternalId());
        $this->assertSame('2026-10-13 19:00:00', $shipment->getExtensionAttributes()->getNetsuiteLastImportDate());
    }

    public function testGetListLoadsTheNetSuiteFieldsOfEveryItem(): void
    {
        $first = $this->createShipment();
        $second = $this->createShipment();
        $second->setData('netsuite_internal_id', null);
        $searchResult = $this->createStub(ShipmentSearchResultInterface::class);
        $searchResult->method('getItems')->willReturn([$first, $second]);

        $result = (new AfterShipmentRepositoryGet())->afterGetList($this->repository(), $searchResult);

        $this->assertSame($searchResult, $result);
        $this->assertSame('24769243', $first->getExtensionAttributes()->getNetsuiteInternalId());
        $this->assertNull($second->getExtensionAttributes()->getNetsuiteInternalId());
    }

    public function testSavingWhatGetReturnedKeepsTheNetSuiteId(): void
    {
        $shipment = $this->createShipment();
        $repository = $this->repository();

        (new AfterShipmentRepositoryGet())->afterGet($repository, $shipment);
        (new BeforeShipmentRepositorySave())->beforeSave($repository, $shipment);

        $this->assertSame('24769243', $shipment->getData('netsuite_internal_id'));
    }

    private function createShipment(): Shipment
    {
        /** @var Shipment $shipment */
        $shipment = (new ObjectManager($this))->getObject(Shipment::class);
        $shipment->setExtensionAttributes(new ShipmentExtension());
        $shipment->setData('netsuite_internal_id', '24769243');
        $shipment->setData('netsuite_last_import_date', '2026-10-13 19:00:00');

        return $shipment;
    }

    private function repository(): ShipmentRepositoryInterface
    {
        return $this->createStub(ShipmentRepositoryInterface::class);
    }
}
