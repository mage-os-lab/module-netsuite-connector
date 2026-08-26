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

namespace MageOS\NetSuiteConnector\Discount\Model\Mapper\Invoice;

use Magento\Sales\Api\Data\InvoiceInterface;
use NetSuite\Classes\CashSale;
use NetSuite\Classes\CashSaleItem;

interface DiscountProviderInterface
{
    /**
     * Check whether given cashSaleItem represents discount
     *
     * @param CashSaleItem $netsuiteItem
     * @return bool
     */
    public function isNSDiscountItem($netsuiteItem);

    /**
     * Update NS discount item with actual discount value and description
     *
     * @param CashSaleItem $netsuiteItem
     * @param float $discountValue
     * @param string $discountDescription
     */
    public function updateNSDiscountItem($netsuiteItem, $discountValue, $discountDescription);

    /**
     * While we try to match discounts and taxes, in case we have multiple invoices tax and discount will be split
     * between them, so we need to manually add them as NetSuite will add them by default only the first time
     *
     * @param CashSale $cashSale
     * @param InvoiceInterface $magentoInvoice
     */
    public function addNSDiscountItem($cashSale, InvoiceInterface $magentoInvoice);
}
