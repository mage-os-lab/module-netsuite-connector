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

namespace MageOS\NetSuiteConnector\Tax\Model\Invoice\Export\TaxManager;

use Magento\Sales\Api\Data\InvoiceInterface;
use NetSuite\Classes\CashSale;
use NetSuite\Classes\CashSaleItem;
use NetSuite\Classes\RecordRef;
use MageOS\NetSuiteConnector\Tax\Model\Invoice\Export\TaxManagerInterface;

/**
 * TaxItemLine - tax manager that implements next logic for tax handling:
 * # taxes are processed on the Mangeto side
 * # connector collects tax info
 * # adds special TaxItem to the Cash Sale to match totals of the order in NetSuite and Magento
 */
class TaxItemLine implements TaxManagerInterface
{
    private const ITEM_DESCRIPTION = 'Sales tax';

    /**
     * @var \MageOS\NetSuiteConnector\Tax\Model\Config\Tax
     */
    private $taxConfig;

    /**
     * TaxItem constructor.
     * @param \MageOS\NetSuiteConnector\Tax\Model\Config\Tax $taxConfig
     */
    public function __construct(\MageOS\NetSuiteConnector\Tax\Model\Config\Tax $taxConfig)
    {
        $this->taxConfig = $taxConfig;
    }

    /**
     * Add Taxes information to the NetSuite CacheSale
     *
     * @param CashSale $cashSale
     * @param $magentoInvoice
     */
    public function addTax(CashSale $cashSale, InvoiceInterface $magentoInvoice): void
    {
        if ($this->taxConfig->getSkipTax()) {
            return;
        }
        foreach ($cashSale->itemList->item as $item) {
            unset($item->taxRate1);
        }
        $taxAmount = (float)$magentoInvoice->getTaxAmount();
        if ($taxAmount) {
            $taxItem = $this->createNSTaxItem($taxAmount);
            $cashSale->itemList->item[] = $taxItem;
            $cashSale->itemList->item = array_values($cashSale->itemList->item);
        }
    }

    /**
     * Create NS cashSaleItem for tax amount
     *
     * @param float $taxAmount
     * @return CashSaleItem
     */
    private function createNSTaxItem(float $taxAmount): CashSaleItem
    {
        $taxItem = new CashSaleItem();
        $taxItem->quantity = 1;
        $taxItem->item = new RecordRef();
        $taxItem->item->internalId = $this->taxConfig->getTaxItemInternalNetsuiteId();
        $taxItem->amount = $taxAmount;
        $taxItem->rate = $taxAmount;
        $taxItem->price = new RecordRef();
        $taxItem->price->internalId = -1;
        $taxItem->description = self::ITEM_DESCRIPTION;
        $taxItem->isTaxable = false;
        $taxItem->itemIsFulfilled = true;
        return $taxItem;
    }
}
