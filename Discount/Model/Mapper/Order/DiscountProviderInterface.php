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

namespace MageOS\NetSuiteConnector\Discount\Model\Mapper\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use NetSuite\Classes\SalesOrder;

interface DiscountProviderInterface
{
    public function addOrderLevelDiscount(SalesOrder $netsuiteOrder, OrderInterface $magentoOrder): void;

    public function addItemLevelDiscount(
        SalesOrder $netsuiteOrder,
        OrderInterface $magentoOrder,
        OrderItemInterface $item
    ): void;

    public function addShippingDiscount(SalesOrder $netsuiteOrder, OrderInterface $magentoOrder): void;
}
