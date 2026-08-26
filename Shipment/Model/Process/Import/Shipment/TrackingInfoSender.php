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
use NetSuite\Classes\ItemFulfillment;
use NetSuite\Classes\Record;

/**
 * This class is responsible for sending a shipment tracking info to customers and for checking whether it happened
 */
class TrackingInfoSender
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
     * @var \Magento\Sales\Api\ShipmentManagementInterface
     */
    private $shipmentManagement;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    private $emulation;

    /**
     * @var \MageOS\NetSuiteConnector\Shipment\Model\Config\ShippingConfig
     */
    private $shippingConfig;

    /**
     * @var  \MageOS\NetSuiteConnector\Shipment\Model\ShipmentRegistry
     */
    private $shipmentRegistry;

    /**
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param \MageOS\NetSuiteConnector\Shipment\Model\Mapper\Trackingnumber $trackingnumberMapper
     * @param \Magento\Sales\Api\ShipmentManagementInterface $shipmentManagement
     * @param \Magento\Store\Model\App\Emulation $emulation
     * @param \MageOS\NetSuiteConnector\Shipment\Model\Config\ShippingConfig $shippingConfig
     * @param \MageOS\NetSuiteConnector\Shipment\Model\ShipmentRegistry $shipmentRegistry
     * @param int $recordLimit
     */
    public function __construct(
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \MageOS\NetSuiteConnector\Shipment\Model\Mapper\Trackingnumber $trackingnumberMapper,
        \Magento\Sales\Api\ShipmentManagementInterface $shipmentManagement,
        \Magento\Store\Model\App\Emulation $emulation,
        \MageOS\NetSuiteConnector\Shipment\Model\Config\ShippingConfig $shippingConfig,
        \MageOS\NetSuiteConnector\Shipment\Model\ShipmentRegistry $shipmentRegistry
    ) {
        $this->shipmentRepository = $shipmentRepository;
        $this->trackingnumberMapper = $trackingnumberMapper;
        $this->shipmentManagement = $shipmentManagement;
        $this->emulation = $emulation;
        $this->shippingConfig = $shippingConfig;
        $this->shipmentRegistry = $shipmentRegistry;
    }

    /**
     * @param Record $itemShipment
     * @return bool
     */
    public function isSentTrackingInformation(Record $itemShipment): bool
    {
        /** @var ItemFulfillment $itemShipment */
        if (!$this->shippingConfig->getSendTrackingInformationOnImport()) {
            return false;
        }

        $existingShipping = $this->shipmentRegistry->getShipmentByNetsuiteId($itemShipment->internalId);
        if ($existingShipping !== null) {
            $existingTrackingCodes = [];
            foreach ($existingShipping->getTracks() as $track) {
                $existingTrackingCodes[] = $track->getTrackNumber();
            }

            $itemShipmentData = $this->trackingnumberMapper->getNormalizedTrackingNumberData($itemShipment);

            foreach ($itemShipmentData as $itemShipmentDataItem) {
                if (!\in_array($itemShipmentDataItem['number'], $existingTrackingCodes)) {
                    return true;
                }
            }
            return false;
        }
        $itemShipmentData = $this->trackingnumberMapper->getNormalizedTrackingNumberData($itemShipment);
        if (\count($itemShipmentData)) {
            return true;
        }
        return false;
    }

    /**
     * Emulate frontend area and send shipping emails
     *
     * @param ShipmentInterface $magentoShipping
     */
    public function sendTrackingInformation($magentoShipping): void
    {
        $this->emulation->startEnvironmentEmulation($magentoShipping->getStoreId(), 'frontend');
        $this->shipmentManagement->notify($magentoShipping->getId());
        $this->emulation->stopEnvironmentEmulation();
        $magentoShipping->setEmailSent(1);
        $this->shipmentRepository->save($magentoShipping);
    }
}
