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
 */


namespace MageOS\NetSuiteConnector\Shipment\Model\Process\Import\Shipment;

use Magento\Sales\Api\Data\ShipmentInterface;
use NetSuite\Classes\Record;

/**
 * This class is responsible for cleaning up the tracking info from the shipment and adding new to the shipment from NS
 */
class TrackingInfo
{
    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    /**
     * @var \MageOS\NetSuiteConnector\Shipment\Model\Mapper\Trackingnumber
     */
    private $trackingnumberMapper;

    /**
     * @var  \Magento\Sales\Api\ShipmentTrackRepositoryInterface
     */
    private $shipmentTrackRepository;

    /**
     * @var  \MageOS\NetSuiteConnector\Shipment\Model\ShipmentRegistry
     */
    private $shipmentRegistry;

    /**
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param \MageOS\NetSuiteConnector\Shipment\Model\Mapper\Trackingnumber $trackingnumberMapper
     * @param \Magento\Sales\Api\ShipmentTrackRepositoryInterface $shipmentTrackRepository
     * @param \MageOS\NetSuiteConnector\Shipment\Model\ShipmentRegistry $shipmentRegistry
     * @param int $recordLimit
     */
    public function __construct(
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \MageOS\NetSuiteConnector\Shipment\Model\Mapper\TrackingNumber $trackingnumberMapper,
        \Magento\Sales\Api\ShipmentTrackRepositoryInterface $shipmentTrackRepository,
        \MageOS\NetSuiteConnector\Shipment\Model\ShipmentRegistry $shipmentRegistry
    ) {
        $this->shipmentRepository = $shipmentRepository;
        $this->trackingnumberMapper = $trackingnumberMapper;
        $this->shipmentTrackRepository = $shipmentTrackRepository;
        $this->shipmentRegistry = $shipmentRegistry;
    }

    /**
     * Remove tracking info from existing shipment
     *
     * @param Record $itemShipment
     */
    public function cleanUpExistingTracking($itemShipment):void
    {
        $existingShipping = $this->shipmentRegistry->getShipmentByNetsuiteId($itemShipment->internalId);
        if ($existingShipping) {
            foreach ($existingShipping->getTracksCollection() as $track) {
                $track->delete();
            }
        }
    }

    /**
     * Add tracking information to magento shipment from NS itemShipment
     *
     * @param Record $itemShipment
     * @param ShipmentInterface $magentoShipping
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function addTrackingInformation($itemShipment, $magentoShipping):void
    {
        $trackingNumbers = $this->trackingnumberMapper->getNormalizedTrackingNumberData($itemShipment);
        if (\count($trackingNumbers)) {
            $magentoTrackingNumbers = [];
            foreach ($trackingNumbers as $trackingNumberData) {
                $magentoTrackingNumber = $this->trackingnumberMapper->getMagentoFormat(
                    $trackingNumberData,
                    $itemShipment->shipMethod
                );
                $magentoTrackingNumber->setParentId($magentoShipping->getId());
                $magentoTrackingNumber->setOrderId($magentoShipping->getOrderId());
                $this->shipmentTrackRepository->save($magentoTrackingNumber);

                $magentoTrackingNumbers[] = $magentoTrackingNumber;
            }
            $magentoShipping->setTracks($magentoTrackingNumbers);
            $this->shipmentRepository->save($magentoShipping);
        }
    }
}
