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
 */

declare(strict_types=1);

namespace MageOS\NetSuiteConnector\Invoice\Model\Mapper;

use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use NetSuite\Classes\CashSale;
use NetSuite\Classes\CashSaleItem;
use NetSuite\Classes\RecordRef;
use NetSuite\Classes\RecordType;

/**
 * This class prepares an invoice data and invoice items data for export to NS
 */
class ExportInvoice
{
    private \Magento\Catalog\Model\ResourceModel\Product $productResource;

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product $productResource
    ) {
        $this->productResource = $productResource;
    }

    /**
     * Update given $cashSale object with data from given $magentoInvoice for sending to NS
     *
     * @param CashSale $cashSale
     * @param InvoiceInterface $magentoInvoice
     * @param OrderInterface $magentoOrder
     * @return CashSale
     */
    public function cleanupNetsuiteCashSale(
        CashSale $cashSale,
        InvoiceInterface $magentoInvoice,
        OrderInterface $magentoOrder
    ): CashSale {

        $cashSale->createdFrom = new RecordRef();
        $cashSale->createdFrom->internalId = $magentoOrder->getData('netsuite_internal_id');
        $cashSale->createdFrom->type = RecordType::salesOrder;

        //adjust invoice elements (an invoice may not have all elements or the same quantity as the order
        foreach ($cashSale->itemList->item as $key => $netsuiteItem) {
            $found = false;

            /** @var \Magento\Sales\Model\Order\Invoice\Item $magentoItem */
            foreach ($magentoInvoice->getItems() as $magentoItem) {
                $netsuiteInternalId = $this->productResource
                    ->getAttributeRawValue($magentoItem->getProductId(), 'netsuite_internal_id', 0);
                if ($this->updateItem($magentoOrder, $magentoItem, $netsuiteItem, $netsuiteInternalId)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                unset($cashSale->itemList->item[$key]);
            }
        }

        $cashSale->itemList->item = array_values($cashSale->itemList->item);

        $cashSale->ccApproved = true;
        $cashSale->chargeIt = false;

        return $cashSale;
    }

    /**
     * @param $magentoOrder
     * @param $magentoItem
     * @param $netsuiteItem
     * @param $netsuiteInternalId
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function updateItem($magentoOrder, $magentoItem, $netsuiteItem, $netsuiteInternalId): bool
    {
        if ($netsuiteInternalId == $netsuiteItem->item->internalId) {
            $this->updateNSCashSaleItem($netsuiteItem, $magentoItem);
            return true;
        }

        return false;
    }

    /**
     * Update cashSale product item with data from corresponding invoice item
     *
     * @param CashSaleItem $netsuiteItem
     * @param \Magento\Sales\Model\Order\Invoice\Item $magentoItem
     */
    private function updateNSCashSaleItem($netsuiteItem, $magentoItem)
    {
        $netsuiteItem->quantity = $magentoItem->getQty();
        $netsuiteItem->amount = $magentoItem->getRowTotal();
        $netsuiteItem->isTaxable = false;
    }
}
