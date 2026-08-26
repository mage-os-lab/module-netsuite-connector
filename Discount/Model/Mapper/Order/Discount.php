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

use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use NetSuite\Classes\RecordRef;
use NetSuite\Classes\RecordType;
use NetSuite\Classes\SalesOrder;
use NetSuite\Classes\SalesOrderItem;
use MageOS\NetSuiteConnector\Core\Exception\ConnectorRuntimeException;

/**
 * This class adds discounts as NS order items to NS order. It can add different discount types: order level discount,
 * item level discount and shipping discount.
 */
class Discount
{
    private \MageOS\NetSuiteConnector\Discount\Model\Config\DiscountConfig $discountConfig;
    private \MageOS\NetSuiteConnector\Discount\Model\Mapper\Order\DiscountProviderInterface $provider;

    public function __construct(
        \MageOS\NetSuiteConnector\Discount\Model\Config\DiscountConfig $discountConfig,
        array $providers = []
    ) {
        if (!$discountConfig->getOrderSkipDiscount()) {
            $provider = $providers[$discountConfig->getLogicSwitch()] ?? null;

            if (!($provider instanceof DiscountProviderInterface)) {
                throw new ConnectorRuntimeException('Discount Provider mismatch with Interface!');
            }

            $this->provider = $provider;
        }
        $this->discountConfig = $discountConfig;
    }

    public function addOrderLevelDiscount(SalesOrder $netsuiteOrder, OrderInterface $magentoOrder): void
    {
        if ($this->discountConfig->getOrderSkipDiscount()) {
            return;
        }
        $this->provider->addOrderLevelDiscount($netsuiteOrder, $magentoOrder);
    }

    public function addItemLevelDiscount(
        SalesOrder $netsuiteOrder,
        OrderInterface $magentoOrder,
        OrderItemInterface $item
    ): void {
        if ($this->discountConfig->getOrderSkipDiscount()) {
            return;
        }
        $this->provider->addItemLevelDiscount($netsuiteOrder, $magentoOrder, $item);
    }

    public function addShippingDiscount(SalesOrder $netsuiteOrder, OrderInterface $magentoOrder): void
    {
        if ($this->discountConfig->getOrderSkipDiscount()) {
            return;
        }
        $this->provider->addShippingDiscount($netsuiteOrder, $magentoOrder);
    }
}
