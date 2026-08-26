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

namespace MageOS\NetSuiteConnector\Discount\Model\Provider\Line;

use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use NetSuite\Classes\RecordRef;
use NetSuite\Classes\RecordType;
use NetSuite\Classes\SalesOrder;
use NetSuite\Classes\SalesOrderItem;

/**
 * This class adds discounts as NS order items to NS order. It can add different discount types: order level discount,
 * item level discount and shipping discount.
 */
class OrderDiscount implements \MageOS\NetSuiteConnector\Discount\Model\Mapper\Order\DiscountProviderInterface
{
    private const DISCOUNT_ITEM_DESCRIPTION = 'Discount';
    private const SHIPPING_DISCOUNT_ITEM_DESCRIPTION = 'Shipping Discount';

    private \MageOS\NetSuiteConnector\Discount\Model\Config\DiscountConfig $discountConfig;
    private \Magento\Framework\Event\ManagerInterface $eventManager;
    private \MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport\OrderItemList $nsOrderItemList;

    public function __construct(
        \MageOS\NetSuiteConnector\Discount\Model\Config\DiscountConfig $discountConfig,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport\OrderItemList $nsOrderItemList,
        private readonly \MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport\Location $nsLocation
    ) {
        $this->discountConfig = $discountConfig;
        $this->eventManager = $eventManager;
        $this->nsOrderItemList = $nsOrderItemList;
    }

    /**
     * Check whether the order level discount is disabled or enabled
     *
     * @return bool
     */
    private function canUseOrderItemLevelDiscount(): bool
    {
        return (bool)$this->discountConfig->getDisableOrderLevelDiscount();
    }

    /**
     * Add discount for whole order as NS item
     *
     * @param SalesOrder $netsuiteOrder
     * @param OrderInterface $magentoOrder
     */
    public function addOrderLevelDiscount(SalesOrder $netsuiteOrder, OrderInterface $magentoOrder): void
    {
        if ($this->canUseOrderItemLevelDiscount()) {
            return;
        }

        $discountAmount = (float)$magentoOrder->getDiscountAmount();
        if (abs($discountAmount) > 0.001) {
            $netsuiteOrderItem = $this->createDiscountItem($magentoOrder, $discountAmount);
            // Dispatch additional event for discounts processing
            $this->eventManager->dispatch(
                'netsuite_new_order_add_discount_before',
                ['magento_order' => $magentoOrder, 'discount' => $netsuiteOrderItem]
            );
            $this->nsOrderItemList->addOrderItemToList($netsuiteOrder, $netsuiteOrderItem);
        }
    }

    /**
     * Add discount for whole given order item as NS item
     *
     * @param SalesOrder $netsuiteOrder
     * @param OrderInterface $magentoOrder
     * @param OrderItemInterface $item
     */
    public function addItemLevelDiscount(
        SalesOrder $netsuiteOrder,
        OrderInterface $magentoOrder,
        OrderItemInterface $item
    ): void {
        if (!$this->canUseOrderItemLevelDiscount()) {
            return;
        }
        $parentItem = $item->getParentItem();
        if ($item->getProductType() === Type::TYPE_SIMPLE
            && $parentItem
            && $parentItem->getProductType() === Configurable::TYPE_CODE
        ) {
            $discountAmount = (float)$parentItem->getDiscountAmount();
        } else {
            $discountAmount = (float)$item->getDiscountAmount();
        }
        if (abs($discountAmount) > 0.001) {
            $discountAmount = -(abs($discountAmount));
            $netsuiteOrderItem = $this->createDiscountItem($magentoOrder, $discountAmount);
            $this->nsOrderItemList->addOrderItemToList($netsuiteOrder, $netsuiteOrderItem);
        }
    }

    /**
     * Add shipping discount for order as NS item
     *
     * @param SalesOrder $netsuiteOrder
     * @param OrderInterface $magentoOrder
     */
    public function addShippingDiscount(SalesOrder $netsuiteOrder, OrderInterface $magentoOrder): void
    {
        if (!$this->canUseOrderItemLevelDiscount()) {
            return;
        }
        $shippingDiscountAmount = (float)$magentoOrder->getShippingDiscountAmount();
        if (abs($shippingDiscountAmount) > 0.001) {
            $shippingDiscountAmount = -(abs($shippingDiscountAmount));
            $netsuiteOrderItem = $this->createDiscountItem($magentoOrder, $shippingDiscountAmount);
            $netsuiteOrderItem->description = self::SHIPPING_DISCOUNT_ITEM_DESCRIPTION;
            $this->nsOrderItemList->addOrderItemToList($netsuiteOrder, $netsuiteOrderItem);
        }
    }

    /**
     * Create NS order item for discount
     *
     * @param OrderInterface $magentoOrder
     * @param float $discountAmount
     * @return SalesOrderItem
     */
    private function createDiscountItem(OrderInterface $magentoOrder, $discountAmount): SalesOrderItem
    {
        $netsuiteOrderItem = new SalesOrderItem();
        $netsuiteOrderItem->description = $magentoOrder->getCouponCode() ?? self::DISCOUNT_ITEM_DESCRIPTION;
        $netsuiteOrderItem->item = new RecordRef();
        $netsuiteOrderItem->item->type = RecordType::discountItem;
        $netsuiteOrderItem->item->internalId = $this->discountConfig->getDiscountItemId();
        $netsuiteOrderItem->price = new RecordRef();
        $netsuiteOrderItem->price->internalId = -1;
        $netsuiteOrderItem->amount = $discountAmount;
        $netsuiteOrderItem->rate = $discountAmount;
        $netsuiteOrderItem->isTaxable = false;
        $this->nsLocation->addLocation($netsuiteOrderItem);
        return $netsuiteOrderItem;
    }
}
