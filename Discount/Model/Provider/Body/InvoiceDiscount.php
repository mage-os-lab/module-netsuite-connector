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

use Magento\Sales\Api\Data\InvoiceInterface;
use NetSuite\Classes\CashSale;
use NetSuite\Classes\CashSaleItem;
use NetSuite\Classes\RecordRef;
use MageOS\NetSuiteConnector\Discount\Model\Mapper\Invoice\DiscountProviderInterface;

/**
 * This class prepares a NS CashSale to include Discount
 */
class InvoiceDiscount implements DiscountProviderInterface
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
     *
     * This method is not used on Body approach
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function isNSDiscountItem($netsuiteItem): bool
    {
        return false;
    }

    // phpcs:disable
    /**
     * This method is not used on Body approach
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function updateNSDiscountItem($netsuiteItem, $discountValue, $discountDescription): void
    {
    }
    // phpcs:enable

    /**
     * @param CashSale $cashSale
     * @param InvoiceInterface $magentoInvoice
     */
    public function addNSDiscountItem($cashSale, InvoiceInterface $magentoInvoice): void
    {
        $discountAmount = (float) $magentoInvoice->getDiscountAmount();

        if (abs($discountAmount) > 0.001 && $this->discountConfig->getDiscountItemId()) {
            if (!$cashSale->discountItem || $cashSale->discountItem->internalId) {
                $cashSale->discountItem = new RecordRef();
                $cashSale->discountItem->internalId = $this->discountConfig->getDiscountItemId();
            }

            $cashSale->discountRate = abs($discountAmount);
        }
    }
}
