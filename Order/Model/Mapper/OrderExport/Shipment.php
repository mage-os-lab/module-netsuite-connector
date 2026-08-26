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

namespace MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport;

use Magento\Sales\Api\Data\OrderInterface;
use NetSuite\Classes\RecordRef;
use NetSuite\Classes\SalesOrder;

/**
 * This class adds shipping method, shipping tax code and shipping amount to NS order
 */
class Shipment
{
    /**
     * Add NS shipping data to NS order
     *
     * Add shipping method, tax code and shipping amount
     *
     * @param SalesOrder $netsuiteOrder
     * @param OrderInterface $magentoOrder
     */
    public function addShipment(SalesOrder $netsuiteOrder, OrderInterface $magentoOrder)
    {
        $netsuiteShippingInternalId = $this->getNetsuiteShippingMethodInternalId($magentoOrder->getShippingMethod());
        if ($netsuiteShippingInternalId !== 0) {
            $netsuiteShippingMethod = new RecordRef();
            $netsuiteShippingMethod->internalId = $netsuiteShippingInternalId;
            $netsuiteOrder->shipMethod = $netsuiteShippingMethod;
        }
        $netsuiteOrder->shippingCost = $magentoOrder->getShippingAmount();
    }

    /**
     * @param $magentoShippingMethodCode
     * @return int
     *
     * @suppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getNetsuiteShippingMethodInternalId($magentoShippingMethodCode): int
    {
        return 0;
    }
}
