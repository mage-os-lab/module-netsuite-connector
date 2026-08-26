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

namespace MageOS\NetSuiteConnector\Tax\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use NetSuite\Classes\SalesOrder;
use NetSuite\Classes\SalesOrderItem;
use MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport\OrderItem;
use MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport\OrderItemList;

class OrderItemAddTax
{
    private \Magento\Catalog\Api\ProductRepositoryInterface $productRepository;
    private \MageOS\NetSuiteConnector\Tax\Model\Config\Tax $taxConfig;
    private \MageOS\NetSuiteConnector\Tax\Model\Tax $tax;
    private ?\Magento\Sales\Api\Data\OrderItemInterface $magentoOrderItem = null;

    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \MageOS\NetSuiteConnector\Tax\Model\Config\Tax $taxConfig,
        \MageOS\NetSuiteConnector\Tax\Model\Tax $tax
    ) {
        $this->productRepository = $productRepository;
        $this->taxConfig = $taxConfig;
        $this->tax = $tax;
    }

    /**
     * @param OrderItemList $subject
     * @param SalesOrder $netsuiteOrder
     * @param SalesOrderItem $netsuiteOrderItem
     * @return array|null
     *
     * @suppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeAddOrderItemToList(
        OrderItemList $subject,
        SalesOrder $netsuiteOrder,
        SalesOrderItem $netsuiteOrderItem
    ) {
        if ($this->taxConfig->getSkipTax()) {
            return null;
        }

        /**
         * We want to trigger this plugin only in specific scenario when the other plugin was triggered first
         */
        if ($this->magentoOrderItem === null) {
            return null;
        }

        $this->tax->getOrderExportTaxManager()->collectOrderItemTax(
            $this->magentoOrderItem,
            $this->productRepository->getById($this->magentoOrderItem->getProductId()),
            $netsuiteOrderItem
        );

        /**
         * Setting it to null will make sure the plugin won't trigger in other calls
         */
        $this->magentoOrderItem = null;
        return [$netsuiteOrder, $netsuiteOrderItem];
    }

    /**
     * This gets triggered before the other plugin which allows us to get the $magentoOrderItem for it
     *
     * @param OrderItem $subject
     * @param OrderItemInterface $magentoOrderItem
     * @param SalesOrder $netsuiteOrder
     * @param OrderInterface $magentoOrder
     * @return null
     *
     * @suppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeAddOrderItem(
        OrderItem $subject,
        OrderItemInterface $magentoOrderItem,
        SalesOrder $netsuiteOrder,
        OrderInterface $magentoOrder
    ) {
        $this->magentoOrderItem = $magentoOrderItem;
        return null;
    }
}
