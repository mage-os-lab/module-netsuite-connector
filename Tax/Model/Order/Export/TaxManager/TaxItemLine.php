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

namespace MageOS\NetSuiteConnector\Tax\Model\Order\Export\TaxManager;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use NetSuite\Classes\RecordRef;
use NetSuite\Classes\SalesOrder;
use NetSuite\Classes\SalesOrderItem;
use MageOS\NetSuiteConnector\Tax\Model\Order\Export\TaxManagerInterface;

/**
 * TaxItemLine - tax manager that implements next logic for tax handling:
 * # taxes are calculated on the Magento side
 * # connector collects tax total
 * # adds special TaxItem line to the Sales Order Item List with the tax total of the Magento Order
 *
 * legacy logic coupling between objects suppressing
 * @SuppressWarnings(PHPMD)
 */
class TaxItemLine implements TaxManagerInterface
{
    private const ITEM_DESCRIPTION = 'Sales tax';

    /**
     * @var \MageOS\NetSuiteConnector\Tax\Model\Config\Tax
     */
    private $taxConfig;
    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;
    /**
     * @var \MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport\OrderItemList
     */
    private $netSuiteOrderItemList;

    /**
     * @var float
     */
    private $taxAmount = 0.0;
    /**
     * @var \MageOS\NetSuiteConnector\Tax\Model\Order\Export\TaxManager\TaxCalculation\TaxPerItem
     */
    private $taxPerItem;

    /**
     * @param \MageOS\NetSuiteConnector\Tax\Model\Config\Tax $taxConfig
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport\OrderItemList $netSuiteOrderItemList
     * @param \MageOS\NetSuiteConnector\Tax\Model\Order\Export\TaxManager\TaxCalculation\TaxPerItem $taxPerItem
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Tax\Model\Config\Tax $taxConfig,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport\OrderItemList $netSuiteOrderItemList,
        \MageOS\NetSuiteConnector\Tax\Model\Order\Export\TaxManager\TaxCalculation\TaxPerItem $taxPerItem,
        private readonly \MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport\Location $nsLocation
    ) {

        $this->taxConfig = $taxConfig;
        $this->eventManager = $eventManager;
        $this->netSuiteOrderItemList = $netSuiteOrderItemList;
        $this->taxPerItem = $taxPerItem;
    }

    /**
     * @inheritDoc
     * @SuppressWarnings("unused")
     */
    public function collectOrderItemTax(
        OrderItemInterface $item,
        ProductInterface $product,
        SalesOrderItem $netsuiteOrderItem
    ): void {
        $this->taxAmount += $this->taxPerItem->getTaxAmount($item, $product);
    }

    /**
     * @inheritDoc
     */
    public function addTax(SalesOrder $netsuiteOrder, OrderInterface $magentoOrder, $taxAmount = null): void
    {
        if (null === $taxAmount) {
            $taxAmount = $this->taxAmount;
        }
        $addTaxItem = new \Magento\Framework\DataObject();
        // Observer may add "Ignore" property = true to skip adding tax as separate item
        $this->eventManager->dispatch(
            'netsuite_new_order_add_tax_item_before',
            [
                'magento_order' => $magentoOrder,
                'netsuite_order' => $netsuiteOrder,
                'add_tax_item' => $addTaxItem
            ]
        );

        //tax setting
        $taxAmount += $magentoOrder->getShippingTaxAmount();
        $taxAmount = round($taxAmount, 2);
        if ($taxAmount && !$addTaxItem->getIgnore()) {
            $netsuiteOrderItem = $this->createTaxItem($taxAmount);
            $this->netSuiteOrderItemList->addOrderItemToList($netsuiteOrder, $netsuiteOrderItem);
        }
        $this->taxAmount = 0;
    }

    /**
     * @inheritDoc
     */
    public function addShippingTax(SalesOrder $netsuiteOrder): void
    {
        $netsuiteOrder->shippingTaxCode = new RecordRef();
        $netsuiteOrder->shippingTaxCode->internalId = $this->taxConfig->getNotTaxableInternalNetsuiteId();
    }

    /**
     * Create NS tax item as separate order item
     *
     * @param float $taxAmount
     * @return SalesOrderItem
     */
    private function createTaxItem($taxAmount): SalesOrderItem
    {
        $netsuiteOrderItem = new SalesOrderItem();
        $netsuiteOrderItem->description = self::ITEM_DESCRIPTION;
        $netsuiteOrderItem->quantity = 1;
        $netsuiteOrderItem->quantityCommitted = 1;
        $netsuiteOrderItem->item = new RecordRef();
        $netsuiteOrderItem->item->internalId = $this->taxConfig->getTaxItemInternalNetsuiteId();
        $netsuiteOrderItem->price = new RecordRef();
        $netsuiteOrderItem->price->internalId = -1;
        $netsuiteOrderItem->amount = $taxAmount;
        $netsuiteOrderItem->rate = $taxAmount;
        $netsuiteOrderItem->isTaxable = false;
        $this->nsLocation->addLocation($netsuiteOrderItem);
        return $netsuiteOrderItem;
    }
}
