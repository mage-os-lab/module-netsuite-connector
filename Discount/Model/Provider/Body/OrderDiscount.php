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

namespace MageOS\NetSuiteConnector\Discount\Model\Provider\Body;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use NetSuite\Classes\CustomFieldList;
use NetSuite\Classes\RecordRef;
use NetSuite\Classes\SalesOrder;
use NetSuite\Classes\StringCustomFieldRef;
use MageOS\NetSuiteConnector\Discount\Model\Mapper\Order\DiscountProviderInterface;

/**
 * This class adds discounts to NS Order directly
 */
class OrderDiscount implements DiscountProviderInterface
{
    private const DISCOUNT_ITEM_DESCRIPTION = 'Discount';
    private const CUSTFIELD_COUPON_CODES = 'custbody_rw_cf_coupon_codes';

    private \MageOS\NetSuiteConnector\Discount\Model\Config\DiscountConfig $discountConfig;

    public function __construct(
        \MageOS\NetSuiteConnector\Discount\Model\Config\DiscountConfig $discountConfig
    ) {
        $this->discountConfig = $discountConfig;
    }

    /**
     * Check whether the order level discount is disabled or enabled
     *
     * @return bool
     */
    public function canUseOrderItemLevelDiscount(): bool
    {
        return false;
    }

    /**
     * Add discount for whole order as NS item
     *
     * @param SalesOrder $netsuiteOrder
     * @param OrderInterface $magentoOrder
     */
    public function addOrderLevelDiscount(SalesOrder $netsuiteOrder, OrderInterface $magentoOrder): void
    {
        $discountAmount = (float)$magentoOrder->getDiscountAmount();
        if (abs($discountAmount) > 0.001 && $this->discountConfig->getDiscountItemId()) {
            $this->addDiscountToOrder($netsuiteOrder, $discountAmount);

            $customFieldList = $netsuiteOrder->customFieldList ?? new CustomFieldList();
            $customFieldList->customField = $customFieldList->customField ?? [];
            $customField = new StringCustomFieldRef();
            $customField->value = $magentoOrder->getCouponCode() ?? self::DISCOUNT_ITEM_DESCRIPTION;
            $customField->scriptId = self::CUSTFIELD_COUPON_CODES;
            $customFieldList->customField[] = $customField;

            $netsuiteOrder->customFieldList = $customFieldList;
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
        $shippingDiscountAmount = (float)$magentoOrder->getShippingDiscountAmount();
        if (abs($shippingDiscountAmount) > 0.001) {
            $this->addDiscountToOrder($netsuiteOrder, $shippingDiscountAmount);
        }
    }

    private function addDiscountToOrder(SalesOrder $netsuiteOrder, float $discountAmount): void
    {
        if (abs($discountAmount) > 0.001 && $this->discountConfig->getDiscountItemId()) {
            if (!$netsuiteOrder->discountItem || !$netsuiteOrder->discountItem->internalId) {
                $netsuiteOrder->discountItem = new RecordRef();
                $netsuiteOrder->discountItem->internalId = $this->discountConfig->getDiscountItemId();
            }

            $netsuiteOrder->discountRate = (float)$netsuiteOrder->discountRate + abs($discountAmount);
            $netsuiteOrder->discountRate = round($netsuiteOrder->discountRate, 2);
        }
    }

    // phpcs:disable
    /**
     * This method is not used on Body approach
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function addItemLevelDiscount(
        SalesOrder $netsuiteOrder,
        OrderInterface $magentoOrder,
        OrderItemInterface $item
    ): void {
    }
    // phpcs:enable
}
