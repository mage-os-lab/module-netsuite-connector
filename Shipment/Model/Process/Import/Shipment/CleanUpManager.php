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

namespace MageOS\NetSuiteConnector\Shipment\Model\Process\Import\Shipment;

use Magento\Sales\Api\Data\ShipmentInterface;
use NetSuite\Classes\Record;

/**
 * Class CleanUpManager - clean existed shipments and trackings before shipment saving
 */
class CleanUpManager
{
    /**
     * @var \Magento\Sales\Api\Data\ShipmentExtensionFactory
     */
    private $shipmentExtensionFactory;

    /**
     * @var \MageOS\NetSuiteConnector\Shipment\Model\ShipmentRegistry
     */
    private $shipmentRegistry;

    /**
     * @var \MageOS\NetSuiteConnector\Shipment\Model\Process\Import\Shipment\TrackingInfo
     */
    private $trackingInfo;

    /**
     * CleanUpManager constructor.
     * @param \Magento\Sales\Api\Data\ShipmentExtensionFactory $shipmentExtensionFactory
     * @param \MageOS\NetSuiteConnector\Shipment\Model\ShipmentRegistry $shipmentRegistry
     * @param TrackingInfo $trackingInfo
     */
    public function __construct(
        \Magento\Sales\Api\Data\ShipmentExtensionFactory $shipmentExtensionFactory,
        \MageOS\NetSuiteConnector\Shipment\Model\ShipmentRegistry $shipmentRegistry,
        \MageOS\NetSuiteConnector\Shipment\Model\Process\Import\Shipment\TrackingInfo $trackingInfo
    ) {
        $this->shipmentExtensionFactory = $shipmentExtensionFactory;
        $this->shipmentRegistry = $shipmentRegistry;
        $this->trackingInfo = $trackingInfo;
    }

    /**
     * @param Record $itemShipment
     * @param $magentoShipping
     */
    public function prepare(Record $itemShipment, $magentoShipping):void
    {
        // it is important to check tracking information before the cleanup, because existing tracking information
        // will be removed from existing shipment

        $this->cleanUpExistingShipment($itemShipment, $magentoShipping);
        $this->trackingInfo->cleanUpExistingTracking($itemShipment);
        $this->addNSLastImportDateAndId($itemShipment, $magentoShipping);

        if (!$magentoShipping->getCommentsCollection()->count()) {
            //we only want to add an auto-comment when the shipment is created, i.e. when there are no comments
            $magentoShipping->addComment(
                "Imported from NetSuite - fulfillment transaction id #{$itemShipment->tranId}",
                false,
                false
            );
        }
    }

    /**
     * Remove items and tracking info from existing shipment
     *
     * @param Record $itemShipment
     * @param ShipmentInterface $magentoShipping
     */
    private function cleanUpExistingShipment($itemShipment, $magentoShipping):void
    {
        $existingShipping = $this->shipmentRegistry->getShipmentByNetsuiteId($itemShipment->internalId);
        if ($existingShipping) {
            foreach ($existingShipping->getAllItems() as $item) {
                $item->delete();
            }
            $magentoShipping->setId($existingShipping->getId());
        }
    }

    /**
     * Remove items and tracking info from existing shipment
     *
     * @param Record $itemShipment
     * @param ShipmentInterface $magentoShipping
     */
    private function addNSLastImportDateAndId($itemShipment, $magentoShipping):void
    {
        $extensionAttributes = $magentoShipping->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->shipmentExtensionFactory->create();
            $magentoShipping->setExtensionAttributes($extensionAttributes);
        }

        $extensionAttributes->setNetsuiteInternalId($itemShipment->internalId);
        $extensionAttributes->setNetsuiteLastImportDate(
            \MageOS\NetSuiteConnector\Core\Model\NetSuite\ConvertDate::fromNetSuiteToSql(
                $itemShipment->lastModifiedDate??'now'
            )
        );
    }
}
