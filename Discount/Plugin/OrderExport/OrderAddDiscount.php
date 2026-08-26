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

namespace MageOS\NetSuiteConnector\Discount\Plugin\OrderExport;

use Magento\Sales\Api\Data\OrderInterface;
use NetSuite\Classes\SalesOrder;
use MageOS\NetSuiteConnector\Order\Model\Mapper\Order;

class OrderAddDiscount
{
    private \MageOS\NetSuiteConnector\Discount\Model\Mapper\Order\Discount $discount;

    public function __construct(
        \MageOS\NetSuiteConnector\Discount\Model\Mapper\Order\Discount $discount
    ) {
        $this->discount = $discount;
    }

    /**
     * @param Order $subject
     * @param SalesOrder $netsuiteOrder
     * @param OrderInterface $magentoOrder
     * @return SalesOrder
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetNetsuiteFormat(
        Order $subject,
        SalesOrder $netsuiteOrder,
        OrderInterface $magentoOrder
    ): SalesOrder {
        $this->discount->addShippingDiscount($netsuiteOrder, $magentoOrder);
        $this->discount->addOrderLevelDiscount($netsuiteOrder, $magentoOrder);

        return $netsuiteOrder;
    }
}
