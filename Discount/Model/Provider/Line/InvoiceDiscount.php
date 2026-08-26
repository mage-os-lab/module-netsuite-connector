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

use Magento\Sales\Api\Data\InvoiceInterface;
use NetSuite\Classes\CashSale;
use NetSuite\Classes\CashSaleItem;
use NetSuite\Classes\RecordRef;
use NetSuite\Classes\RecordType;

/**
 * This class prepares a NS cashSaleItem to represent discount
 */
class InvoiceDiscount implements \MageOS\NetSuiteConnector\Discount\Model\Mapper\Invoice\DiscountProviderInterface
{
    private const ITEM_DESCRIPTION = 'Discount';

    private \MageOS\NetSuiteConnector\Discount\Model\Config\DiscountConfig $discountConfig;

    public function __construct(
        \MageOS\NetSuiteConnector\Discount\Model\Config\DiscountConfig $discountConfig
    ) {
        $this->discountConfig = $discountConfig;
    }

    /**
     * Check whether given cashSaleItem represents discount
     *
     * @param CashSaleItem $netsuiteItem
     * @return bool
     */
    public function isNSDiscountItem($netsuiteItem)
    {
        return $netsuiteItem->item->internalId == $this->discountConfig->getDiscountItemId();
    }

    /**
     * Update NS discount item with actual discount value and description
     *
     * @param CashSaleItem $netsuiteItem
     * @param float $discountValue
     * @param string $discountDescription
     */
    public function updateNSDiscountItem($netsuiteItem, $discountValue, $discountDescription)
    {
        $discountValue = -(abs($discountValue));
        $netsuiteItem->item->type = RecordType::discountItem;
        $netsuiteItem->amount = $discountValue;
        $netsuiteItem->rate =  $discountValue;
        $netsuiteItem->price = new RecordRef();
        $netsuiteItem->price->internalId = -1;
        $netsuiteItem->description = $discountDescription ?? self::ITEM_DESCRIPTION;
        $netsuiteItem->isTaxable = false;
    }

    /**
     * While we try to match discounts and taxes, in case we have multiple invoices tax and discount will be split
     * between them, so we need to manually add them as NetSuite will add them by default only the first time
     *
     * @param CashSale $cashSale
     * @param InvoiceInterface $magentoInvoice
     */
    public function addNSDiscountItem($cashSale, InvoiceInterface $magentoInvoice)
    {
        $discountItemId = $this->discountConfig->getDiscountItemId();
        $discountAmount = (float) $magentoInvoice->getDiscountAmount();
        if ($discountAmount) {
            $found = false;
            foreach ($cashSale->itemList->item as $item) {
                if ($item->item->internalId == $discountItemId) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $discountItem = $this->createNSDiscountItem($discountAmount);
                $cashSale->itemList->item[] = $discountItem;
                $cashSale->itemList->item = array_values($cashSale->itemList->item);
            }
        }
    }

    /**
     * Create NS cashSaleItem for discount amount
     *
     * @param float $discountAmount
     * @return CashSaleItem
     */
    private function createNSDiscountItem($discountAmount)
    {
        $discountItem = new CashSaleItem();
        $discountItem->quantity = 1;
        $discountItem->item = new RecordRef();
        $discountItem->item->internalId = $this->discountConfig->getDiscountItemId();
        $discountItem->amount = $discountAmount;
        $discountItem->rate = $discountAmount;
        $discountItem->price = new RecordRef();
        $discountItem->price->internalId = -1;
        $discountItem->description = self::ITEM_DESCRIPTION;
        $discountItem->isTaxable = false;
        $discountItem->itemIsFulfilled = true;
        return $discountItem;
    }
}
