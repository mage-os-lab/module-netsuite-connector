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

namespace MageOS\NetSuiteConnector\Shipment\Plugin;

use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Api\Data\ShipmentInterface;

/**
 * This class copies netsuite_internal_id and netsuite_last_import_date from shipment extension data object into shipment model
 * before save
 */
class BeforeShipmentRepositorySave
{
    /**
     * Copy netsuite_internal_id and netsuite_last_import_date from extension data object into target object
     *
     * @param ShipmentRepositoryInterface $subject
     * @param ShipmentInterface $shipment
     * @return array
     * @SuppressWarnings("unused")
     */
    public function beforeSave(ShipmentRepositoryInterface $subject, ShipmentInterface $shipment): array
    {
        $extensionAttribute = $shipment->getExtensionAttributes();
        $shipment->setNetsuiteInternalId($extensionAttribute->getNetsuiteInternalId());
        $shipment->setNetsuiteLastImportDate($extensionAttribute->getNetsuiteLastImportDate());
        return [$shipment];
    }
}
