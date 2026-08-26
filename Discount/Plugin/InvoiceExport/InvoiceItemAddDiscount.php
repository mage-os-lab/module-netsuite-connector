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

namespace MageOS\NetSuiteConnector\Discount\Plugin\InvoiceExport;

class InvoiceItemAddDiscount
{
    private array $discountValue = [];
    private \MageOS\NetSuiteConnector\Discount\Model\Mapper\Invoice\Discount $discount;

    public function __construct(
        \MageOS\NetSuiteConnector\Discount\Model\Mapper\Invoice\Discount $discount
    ) {
        $this->discount = $discount;
    }

    /**
     * @param \MageOS\NetSuiteConnector\Invoice\Model\Mapper\ExportInvoice $subject
     * @param bool $result
     * @param $magentoOrder
     * @param $magentoItem
     * @param $netsuiteItem
     * @param $netsuiteInternalId
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterUpdateItem(
        \MageOS\NetSuiteConnector\Invoice\Model\Mapper\ExportInvoice $subject,
        bool $result,
        $magentoOrder,
        $magentoItem,
        $netsuiteItem,
        $netsuiteInternalId
    ): bool {
        $magentoOrderId = $magentoOrder->getId();
        if ($netsuiteInternalId == $netsuiteItem->item->internalId) {
            $this->discountValue[$magentoOrderId] = $this->getInvoiceItemDiscount($magentoItem);
            return $result;
        }

        // Discount item
        if (isset($this->discountValue[$magentoOrderId])
            && $this->discountValue[$magentoOrderId] > 0.001
            && $this->discount->isNSDiscountItem($netsuiteItem)
        ) {
            $this->discount->updateNSDiscountItem(
                $netsuiteItem,
                $this->discountValue[$magentoOrderId],
                $magentoOrder->getDiscountDescription()
            );
            unset($this->discountValue[$magentoOrderId]);

            return true;
        }

        return $result;
    }

    /**
     * Get invoice item discount
     *
     * Use parent item if exists
     *
     * @param \Magento\Sales\Model\Order\Invoice\Item $magentoItem
     * @return float
     */
    private function getInvoiceItemDiscount($magentoItem): ?float
    {
        $discountValue = $magentoItem->getDiscountAmount();
        /**
         * Get discount amount from parent configurable item for simple
         */
        /** @var \Magento\Sales\Model\Order\Item $orderItem */
        $orderItem = $magentoItem->getOrderItem();
        if (!$discountValue && $orderItem->getParentItemId()) {
            $discountValue = $orderItem->getParentItem()->getDiscountAmount();
        }
        return $discountValue ? (float) $discountValue : null;
    }
}
