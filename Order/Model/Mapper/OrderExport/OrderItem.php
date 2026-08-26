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

use Magento\Bundle\Model\Product\Type as BundleProduct;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableProduct;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use NetSuite\Classes\RecordRef;
use NetSuite\Classes\SalesOrder;
use NetSuite\Classes\SalesOrderItem;
use MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException;

/**
 * This class adds NS order item to NS order
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderItem
{
    private array $fixedPriceBundles = [];
    private \MageOS\NetSuiteConnector\Core\Helper\EavHelper $eavHelper;
    private \Magento\Catalog\Api\ProductRepositoryInterface $productRepository;
    private \Magento\Framework\Event\ManagerInterface $eventManager;
    private \MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport\OrderItemList $nsOrderItemList;
    private \MageOS\NetSuiteConnector\Product\Model\Config\ProductConfig $productConfig;

    public function __construct(
        \MageOS\NetSuiteConnector\Core\Helper\EavHelper $eavHelper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport\OrderItemList $nsOrderItemList,
        \MageOS\NetSuiteConnector\Product\Model\Config\ProductConfig $productConfig,
        private readonly \MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport\Location $nsLocation
    ) {
        $this->eavHelper = $eavHelper;
        $this->productRepository = $productRepository;
        $this->eventManager = $eventManager;
        $this->nsOrderItemList = $nsOrderItemList;
        $this->productConfig = $productConfig;
    }

    /**
     * Check whether given magento order item should be sent to NS or should be skipped
     *
     * It depends on product type
     *
     * @param OrderItemInterface $magentoOrderItem
     * @param OrderInterface $magentoOrder
     * @return bool
     * @throws NoSuchEntityException
     */
    public function shouldSkipOrderItem(OrderItemInterface $magentoOrderItem, OrderInterface $magentoOrder): bool
    {
        // skip configurable product, send only it's simple item
        if (in_array($magentoOrderItem->getProductType(), [ConfigurableProduct::TYPE_CODE], true)) {
            return true;
        }
        // skip bundle items
        $parentItem = $magentoOrderItem->getParentItem();
        if ($parentItem && $parentItem->getProductType() === BundleProduct::TYPE_CODE) {
            return true;
        }
        if ($this->productIsPartOfFixedPriceBundle($magentoOrderItem, $magentoOrder->getItems())
            && !$this->productConfig->getPushLineItemsWithBundles()
        ) {
            return true;
        }
        return false;
    }

    /**
     * Add NS order item to NS order
     *
     * This includes all item-related data
     *
     * @param OrderItemInterface $magentoOrderItem
     * @param SalesOrder $netsuiteOrder
     * @param OrderInterface $magentoOrder
     */
    public function addOrderItem(
        OrderItemInterface $magentoOrderItem,
        SalesOrder $netsuiteOrder,
        OrderInterface $magentoOrder
    ) {
        $product = $this->getItemProduct($magentoOrderItem);

        $netsuiteOrderItem = $this->createOrderItem($magentoOrderItem, $product);
        $this->nsLocation->addLocation($netsuiteOrderItem);

        $this->eventManager->dispatch('netsuite_new_order_item_send_before', [
            'magento_order' => $magentoOrder,
            'netsuite_order' => $netsuiteOrder,
            'magento_order_item' => $magentoOrderItem,
            'netsuite_order_item' => $netsuiteOrderItem
        ]);
        $this->nsOrderItemList->addOrderItemToList($netsuiteOrder, $netsuiteOrderItem);
    }

    /**
     * Load product for given order item
     *
     * @param OrderItemInterface $magentoOrderItem
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    private function getItemProduct($magentoOrderItem): ProductInterface
    {
        return $this->productRepository->getById($magentoOrderItem->getProductId());
    }

    /**
     * Create NS order item
     *
     * @param OrderItemInterface $magentoOrderItem
     * @param ProductInterface $product
     * @return SalesOrderItem
     */
    public function createOrderItem(OrderItemInterface $magentoOrderItem, ProductInterface $product): SalesOrderItem
    {
        $netsuiteOrderItem = new SalesOrderItem();
        $netsuiteOrderItem->description = $this->getProductDescription($magentoOrderItem);
        $netsuiteOrderItem->quantity = $magentoOrderItem->getQtyOrdered();
        $netsuiteOrderItem->quantityCommitted = $magentoOrderItem->getQtyOrdered();
        $netsuiteOrderItem->item = new RecordRef();
        $netsuiteOrderItem->item->internalId = $this->getItemInternalId($magentoOrderItem, $product);
        $netsuiteOrderItem->price = new RecordRef();
        $netsuiteOrderItem->price->internalId = -1;
        $netsuiteOrderItem->isTaxable = false;
        $netsuiteOrderItem->rate = $this->getPrice($magentoOrderItem, $product);
        return $netsuiteOrderItem;
    }

    /**
     * Generate description for NS order item
     *
     * It includes product name + possible options
     *
     * @param OrderItemInterface $orderItem
     * @return mixed|null|string
     */
    private function getProductDescription(OrderItemInterface $orderItem)
    {
        $description = $orderItem->getName();
        $customOptions = $orderItem->getProductOptions();
        if (\is_array($customOptions) && isset($customOptions['options']) && \count($customOptions['options'])) {
            $customOptions = $customOptions['options'];
            $description .= ' - ';
            foreach ($customOptions as $option) {
                $description .= $option['label'] . ':' . $option['print_value'] . ', ';
            }
            $description = preg_replace('/, $/', '', $description);
        }
        return $description;
    }

    /**
     * Check whether given magento order item is a part of fixed price bundle product
     *
     * @param OrderItemInterface $orderItem
     * @param array $allOrderItems
     * @return boolean
     * @throws NoSuchEntityException
     */
    private function productIsPartOfFixedPriceBundle(OrderItemInterface $orderItem, $allOrderItems): bool
    {
        // Bundles can only contain simple products
        if ($orderItem->getProductType() != Type::TYPE_SIMPLE) {
            return false;
        }

        // Fixed price bundles have zero price for the components
        if ($orderItem->getRowTotal() != 0) {
            return false;
        }

        foreach ($allOrderItems as $currentOrderItem) {
            if ($currentOrderItem->getProductType() == BundleProduct::TYPE_CODE) {
                $bundleProduct = $this->productRepository->getById($currentOrderItem->getProductId());

                $selection = $bundleProduct->getTypeInstance()->getSelectionsCollection(
                    $bundleProduct->getTypeInstance()->getOptionsIds($bundleProduct),
                    $bundleProduct
                );
                foreach ($selection as $child) {
                    if ($child->getId() == $orderItem->getProductId()) {
                        return true;
                    }
                }
            }
            return false;
        }
        return false;
    }

    /**
     * Get netsuite internal ID for given order item
     *
     * @param OrderItemInterface $magentoOrderItem
     * @param ProductInterface $product
     * @return mixed
     * @throws DataIntegrityException
     */
    private function getItemInternalId(OrderItemInterface $magentoOrderItem, ProductInterface $product)
    {
        $internalId = $product->getCustomAttribute('netsuite_internal_id') ?
            (string)$product->getCustomAttribute('netsuite_internal_id')->getValue() :
            0;

        $productType = $magentoOrderItem->getProductType();
        if ($productType === BundleProduct::TYPE_CODE) {
            $internalId = $this->eavHelper->mapBundleOptionToInternalId($magentoOrderItem) ?? $internalId;
        }

        if (!$internalId) {
            throw new DataIntegrityException('Product ' . $magentoOrderItem->getProductId()
                . ' has empty NetSuite internal ID');
        }
        return $internalId;
    }

    /**
     * Get order item price
     *
     * It depends on product type
     *
     * @param OrderItemInterface $magentoOrderItem
     * @param ProductInterface $product
     * return float
     * @return float|int|null
     */
    private function getPrice(OrderItemInterface $magentoOrderItem, ProductInterface $product)
    {
        $price = $magentoOrderItem->getPrice();
        if (!((float)$magentoOrderItem->getRowTotal()) && $magentoOrderItem->getParentItemId()) {
            $price = $magentoOrderItem->getParentItem()->getPrice();
        }

        if ($magentoOrderItem->getProductType() == BundleProduct::TYPE_CODE) {
            if ($product->getPrice() == 0) {
                //We remove the price here as a zero-priced bundle has the price of its parts.
                //Since we list the parts as simple products, the price will be doubled otherwise
                $price = 0;
            } else {
                //fixed price bundles. In this case, we must remove the price of the simple parts
                $this->fixedPriceBundles[$magentoOrderItem->getId()] = true;
            }
        }
        if ($magentoOrderItem->getProductType() == Type::TYPE_SIMPLE && $magentoOrderItem->getParentItemId()
            && isset($this->fixedPriceBundles[$magentoOrderItem->getParentItemId()])
        ) {
            $price = 0;
        }
        return $price;
    }
}
