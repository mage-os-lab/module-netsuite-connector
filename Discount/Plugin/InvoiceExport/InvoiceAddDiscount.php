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

use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use NetSuite\Classes\CashSale;

class InvoiceAddDiscount
{
    private \MageOS\NetSuiteConnector\Discount\Model\Mapper\Invoice\Discount $discount;

    public function __construct(
        \MageOS\NetSuiteConnector\Discount\Model\Mapper\Invoice\Discount $discount
    ) {
        $this->discount = $discount;
    }

    /**
     * @param \MageOS\NetSuiteConnector\Invoice\Model\Mapper\ExportInvoice $subject
     * @param CashSale $result
     * @param CashSale $cashSale
     * @param InvoiceInterface $magentoInvoice
     * @param OrderInterface $magentoOrder
     * @return CashSale
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCleanupNetsuiteCashSale(
        \MageOS\NetSuiteConnector\Invoice\Model\Mapper\ExportInvoice $subject,
        CashSale $result,
        CashSale $cashSale,
        InvoiceInterface $magentoInvoice,
        OrderInterface $magentoOrder
    ): CashSale {
        $this->discount->addNSDiscountItem($result, $magentoInvoice);

        return $result;
    }
}
