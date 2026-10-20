<?php
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

declare(strict_types=1);

namespace MageOS\NetSuiteConnector\Shipment\Plugin;

use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentSearchResultInterface;

/**
 * This class loads the NetSuite fields of the shipment into its extension attributes, so they are always present
 * and saving the shipment through the repository keeps them
 */
class AfterShipmentRepositoryGet
{
    /**
     * Load the NetSuite fields into the extension attributes of the shipment
     *
     * @param ShipmentRepositoryInterface $subject
     * @param ShipmentInterface $shipment
     * @return ShipmentInterface
     * @SuppressWarnings("unused")
     */
    public function afterGet(ShipmentRepositoryInterface $subject, ShipmentInterface $shipment): ShipmentInterface
    {
        $this->loadShipment($shipment);
        return $shipment;
    }

    /**
     * Load the NetSuite fields into the extension attributes of every shipment in the result
     *
     * @param ShipmentRepositoryInterface $subject
     * @param ShipmentSearchResultInterface $searchResult
     * @return ShipmentSearchResultInterface
     * @SuppressWarnings("unused")
     */
    public function afterGetList(
        ShipmentRepositoryInterface $subject,
        ShipmentSearchResultInterface $searchResult
    ): ShipmentSearchResultInterface {
        foreach ($searchResult->getItems() as $shipment) {
            $this->loadShipment($shipment);
        }
        return $searchResult;
    }

    /**
     * Copy the NetSuite fields of the shipment into its extension attributes
     *
     * @param ShipmentInterface $shipment
     * @return void
     */
    private function loadShipment(ShipmentInterface $shipment): void
    {
        $extensionAttributes = $shipment->getExtensionAttributes();
        if (!$extensionAttributes) {
            return;
        }
        $extensionAttributes->setNetsuiteInternalId($shipment->getData('netsuite_internal_id'));
        $extensionAttributes->setNetsuiteLastImportDate($shipment->getData('netsuite_last_import_date'));
    }
}
