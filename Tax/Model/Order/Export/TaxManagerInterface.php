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
 *
 */
declare(strict_types=1);

namespace MageOS\NetSuiteConnector\Tax\Model\Order\Export;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use NetSuite\Classes\SalesOrder;
use NetSuite\Classes\SalesOrderItem;

/**
 * Order Export Tax Manager tax management business logic
 * Used during Magento Order Export to NetSuite.
 *
 * @spi
 */
interface TaxManagerInterface
{

    /**
     * Method adds the OrderItem tax amount to general Tax Amount
     * @param OrderItemInterface $item
     * @param ProductInterface $product
     * @param SalesOrderItem $netsuiteOrderItem
     */
    public function collectOrderItemTax(
        OrderItemInterface $item,
        ProductInterface $product,
        SalesOrderItem $netsuiteOrderItem
    ): void;

    /**
     * Method adds specific data to NetSuite Order Shipment
     * @param SalesOrder $netsuiteOrder
     * @return mixed
     */
    public function addShippingTax(SalesOrder $netsuiteOrder): void;

    /**
     * Add Taxes information to the NetSuite Sales Order
     *
     * @param SalesOrder $netsuiteOrder
     * @param OrderInterface $magentoOrder
     * @param float|null $taxAmount
     */
    public function addTax(SalesOrder $netsuiteOrder, OrderInterface $magentoOrder, $taxAmount = null): void;
}
