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

namespace MageOS\NetSuiteConnector\Refund\Model\Mapper\CreditMemo;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use NetSuite\Classes\CreditMemoItem as NSCreditMemoItem;
use NetSuite\Classes\Record;

/**
 * This class creates a magento creditmemo items from creditMemo record object retrieved from NS
 */
class Items
{
    private const XML_PATH_TAX_ITEM_INTERNAL_NETSUITE_ID = 'mageos_netsuite/tax/tax_item_internal_netsuite_id';

    public function __construct(
        private readonly \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        private readonly \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly \Magento\Sales\Api\Data\CreditmemoItemInterfaceFactory $itemFactory,
        private readonly \MageOS\NetSuiteConnector\Discount\Model\Config\DiscountConfig $salesConfig,
        private readonly \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Method builds array of creditmemo items based on the NetSuite data
     *
     * @param Record $netSuiteObject
     * @param OrderInterface $order
     * @return array
     */
    public function getItems(Record $netSuiteObject, OrderInterface $order): array
    {
        $productIdToOrderItem = [];
        foreach ($order->getItems() as $orderItem) {
            $productIdToOrderItem[$orderItem->getProductId()] = $orderItem;
        }

        $items = [];
        foreach ($netSuiteObject->itemList->item as $cmItem) {
            if (!$this->canProcessItem($cmItem)) {
                continue;
            }

            /** @var CreditmemoItemInterface $item */
            $item = $this->itemFactory->create();

            $product = $this->getProductByNetSuiteId((int)$cmItem->item->internalId);

            if (!$product) {
                throw new \RuntimeException("Product not found for netsuite #{$cmItem->item->internalId}");
            }

            /** @var OrderItemInterface $orderItem */
            $orderItem = $productIdToOrderItem[$product->getId()] ?? null;
            if (!$orderItem) {
                // skip all the items which are not in the order
                continue;
            }

            $orderItemId = $orderItem->getItemId();
            if ($orderItem->getParentItem()) {
                // if we won't set to parent item id - it won't appear in the
                // credit memo
                $orderItem = $orderItem->getParentItem();
                $orderItemId = $orderItem->getItemId();
                // also we need to use the price from the parent item to
                // include prices of all configured options
            }

            $rowTotal = $orderItem->getPrice();

            $item->setOrderItemId($orderItemId);
            $item->setProductId($product->getId());
            $item->setSku($orderItem->getSku());

            $item->setName($cmItem->description);
            $item->setQty($cmItem->quantity);

            $item->setRowTotal($rowTotal);
            $item->setBasePrice($rowTotal);
            $item->setPrice($rowTotal);

            $taxAmount = round($rowTotal * ($cmItem->taxRate1 / 100), 2);
            $item->setTaxAmount($taxAmount);

            $items[] = $item;
        }
        return $items;
    }

    /**
     * Get magento product by NS internal ID
     *
     * @param int $internalNetSuiteId
     * @return ProductInterface|null
     */
    private function getProductByNetSuiteId(int $internalNetSuiteId): ?ProductInterface
    {
        $this->searchCriteriaBuilder->addFilter('netsuite_internal_id', $internalNetSuiteId);
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $products = $this->productRepository->getList($searchCriteria)->getItems();
        if (count($products)) {
            return array_pop($products);
        }
        return null;
    }

    /**
     * Check whether given NS creditmemo item can be processed
     *
     * Only item which represent products can be processed. Discount and tax items are ignored.
     *
     * @param NSCreditMemoItem $item
     * @return bool
     */
    private function canProcessItem($item): bool
    {
        if ($item->item->internalId === $this->salesConfig->getDiscountItemId()) {
            return false;
        }

        $taxItemInternalId = $this->scopeConfig->getValue(self::XML_PATH_TAX_ITEM_INTERNAL_NETSUITE_ID);
        if ($taxItemInternalId !== null && $item->item->internalId === (int)$taxItemInternalId) {
            return false;
        }

        return true;
    }
}
