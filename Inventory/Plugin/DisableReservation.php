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

namespace MageOS\NetSuiteConnector\Inventory\Plugin;

use Magento\InventorySales\Model\PlaceReservationsForSalesEvent;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesEventInterface;

/**
 * This class disable reservation for events not connected with direct order placement.
 */
class DisableReservation
{
    /**
     * this plugin disable reservation creation for all scenarios except order place
     * @param PlaceReservationsForSalesEvent $subject
     * @param \Closure $proceed
     * @param array $items
     * @param SalesChannelInterface $salesChannel
     * @param SalesEventInterface $salesEvent
     * @return mixed
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        PlaceReservationsForSalesEvent $subject,
        \Closure $proceed,
        array $items,
        SalesChannelInterface $salesChannel,
        SalesEventInterface $salesEvent
    ) {
        if (in_array(
            $salesEvent->getType(),
            [SalesEventInterface::EVENT_ORDER_PLACED, SalesEventInterface::EVENT_ORDER_PLACE_FAILED,
            SalesEventInterface::EVENT_SHIPMENT_CREATED]
        )) {
            return $proceed($items, $salesChannel, $salesEvent);
        }
    }
}
