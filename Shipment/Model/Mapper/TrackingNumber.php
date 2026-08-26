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


namespace MageOS\NetSuiteConnector\Shipment\Model\Mapper;

use Magento\Sales\Api\Data\ShipmentTrackInterface;
use NetSuite\Classes\Record;

/**
 * This class is responsible for converting NS shipping track data into magento tracking
 */
class TrackingNumber
{
    /**
     * @var \Magento\Sales\Api\Data\ShipmentTrackInterfaceFactory
     */
    private $shipmentTrackFactory;

    /**
     * @var \MageOS\NetSuiteConnector\Shipment\Model\Config\ShippingConfig
     */
    private $shippingConfig;

    /**
     * Trackingnumber constructor.
     * @param \Magento\Sales\Api\Data\ShipmentTrackInterfaceFactory $shipmentTrackFactory
     * @param \MageOS\NetSuiteConnector\Shipment\Model\Config\ShippingConfig $shippingConfig
     */
    public function __construct(
        \Magento\Sales\Api\Data\ShipmentTrackInterfaceFactory $shipmentTrackFactory,
        \MageOS\NetSuiteConnector\Shipment\Model\Config\ShippingConfig $shippingConfig
    ) {
        $this->shipmentTrackFactory = $shipmentTrackFactory;
        $this->shippingConfig = $shippingConfig;
    }

    /**
     * Retrieve tracking numbers data from NS shipment object.
     * Four tracking providers supported:
     * default
     * FedEx
     * Ups
     * Usps
     * Each has its own format in the Shipment
     * @param Record $netsuiteShipment
     * @return array
     */
    public function getNormalizedTrackingNumberData(Record $netsuiteShipment): array
    {
        //NetSuite will store packages in different data structures based on the shipping carrier.
        //This method will normalize the tracking numbers in a single array
        $trackingNumbers = [];
        $supportedTrackerProviders = [
            'packageList' => '',
            'packageFedExList' => 'FedEx',
            'packageUpsList' => 'Ups',
            'packageUspsList' => 'Usps',
        ];

        foreach ($supportedTrackerProviders as $key => $value) {
            $package = 'package' . $value;
            $packageTrackingNumber = 'packageTrackingNumber' . $value;
            $packageDescr = 'packageDescr' . $value;
            if (isset($netsuiteShipment->$key) && is_array($netsuiteShipment->$key->$package)) {
                foreach ($netsuiteShipment->$key->$package as $netsuitePackage) {
                    if (!empty($netsuitePackage->$packageTrackingNumber)) {
                        $trackingNumbers[] = [
                            'number' => $netsuitePackage->$packageTrackingNumber,
                            'description' => $netsuitePackage->$packageDescr ?? ''
                        ];
                    }
                }
            }
        }

        return $trackingNumbers;
    }

    /**
     * Convert tracking data from NS into magento tracking object
     *
     * @param array $trackData
     * @param Record; $shipMethod
     * @return ShipmentTrackInterface
     */
    public function getMagentoFormat($trackData, $shipMethod)
    {
        $magentoTracking = $this->shipmentTrackFactory->create();
        $carrierCode = $this->getMagentoCarrierCodeFromNetsuiteInternalId($shipMethod->internalId);

        $magentoTracking->setTrackNumber($trackData['number'])
            ->setCarrierCode($carrierCode)
            ->setTitle($trackData['description']);

        return $magentoTracking;
    }

    /**
     * Get magento carrier code for given NS carrier
     *
     * @param int $internalNetsuiteId
     * @return string
     */
    public function getMagentoCarrierCodeFromNetsuiteInternalId($internalNetsuiteId)
    {
        $carrierCode = $this->shippingConfig->getDefaultTrackingCodeCarrier();
        $carrierCodeMap = $this->shippingConfig->getTrackingMapping();
        foreach ($carrierCodeMap as $carrierCodeMapItem) {
            if ($carrierCodeMapItem['internal_netsuite_id'] == $internalNetsuiteId) {
                return $carrierCodeMapItem['carrier_type'];
            }
        }
        return $carrierCode;
    }
}
