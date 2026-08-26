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


namespace MageOS\NetSuiteConnector\Shipment\MultiSource\Plugin;

use NetSuite\Classes\Record;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\CustomFieldAccess;

/**
 * Plugin to add specific for multi source module logic with tracking number normalizing
 */
class TrackingNumberMultiSource
{
    const CUST_FIELD_FOR_TRACKING_NUMBER = 'custcol_rw_cf_msi_tracking_number';

    public function __construct(
        private readonly \MageOS\NetSuiteConnector\Inventory\Model\Config\InventoryMode $inventoryMode
    ) {
    }

    /**
     * method change nsc/shipment logic to use custom field of shipment item with tracking number data
     * if there are no tracking number in the items found we fallback to the package logic from nsc/shipment
     *
     * @param \MageOS\NetSuiteConnector\Shipment\Model\Mapper\TrackingNumber $subject
     * @param array $originalTrackingNumbers
     * @param Record $netsuiteShipment
     * @return array
     * @SuppressWarnings("unused")
     */
    public function afterGetNormalizedTrackingNumberData(
        \MageOS\NetSuiteConnector\Shipment\Model\Mapper\TrackingNumber $subject,
        array $originalTrackingNumbers,
        Record $netsuiteShipment
    ): array {
        if (!$this->inventoryMode->isMulti()) {
            return $originalTrackingNumbers;
        }

        $result = [];
        //build array of custom fields tracking numbers
        $trackingNumbers = [];
        $needToMerge = false;
        foreach ($netsuiteShipment->itemList->item as $netsuiteShipmentItem) {
            $trackingNumber = CustomFieldAccess::get(
                $netsuiteShipmentItem,
                self::CUST_FIELD_FOR_TRACKING_NUMBER
            );
            if (null !== $trackingNumber) {
                $trackingNumbers[] = $trackingNumber;
            } else {
                $needToMerge = true;
            }

        }
        $trackingNumbers = array_unique($trackingNumbers);
        // when no item have tracking number we return original result as fallback logic
        if (empty($trackingNumbers)) {
            return $originalTrackingNumbers;
        }
        /**
         * we check old result for descriptions and build new array
         * as description we use original data retrieved from package in intercepted method
         * if no description exist we set empty description
         */
        foreach ($originalTrackingNumbers as $key => $originalTrackingNumber) {
            foreach ($trackingNumbers as $trackingNumber) {
                if ($originalTrackingNumbers['number'] == $trackingNumber) {
                    $result[] = $originalTrackingNumber;
                    unset($originalTrackingNumbers[$key]);
                } else {
                    $result[] = $result[] = ['number' => $trackingNumber, 'description' => ''];
                }
            }
        }
        /**
         * if some items have empty custom fields we need to merge the custom fields tracking numbers and original
         * tracking numbers from the package(intercepted method logic)
         */
        if ($needToMerge) {
            $result = array_merge($result, $originalTrackingNumbers);
        }
        return $result;
    }
}
