<?php

namespace MageOS\NetSuiteConnector\Tax\Plugin;

use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use NetSuite\Classes\CashSale;
use MageOS\NetSuiteConnector\Invoice\Model\Mapper\ExportInvoice;

class InvoiceAddTax
{
    private \MageOS\NetSuiteConnector\Tax\Model\Tax $tax;
    private \MageOS\NetSuiteConnector\Tax\Model\Config\Tax $taxConfig;

    public function __construct(
        \MageOS\NetSuiteConnector\Tax\Model\Config\Tax $taxConfig,
        \MageOS\NetSuiteConnector\Tax\Model\Tax $tax
    ) {
        $this->tax = $tax;
        $this->taxConfig = $taxConfig;
    }

    /**
     * @param ExportInvoice $subject
     * @param CashSale $result
     * @param CashSale $cashSale
     * @param InvoiceInterface $magentoInvoice
     * @param OrderInterface $magentoOrder
     * @return CashSale
     *
     * @suppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCleanupNetsuiteCashSale(
        ExportInvoice $subject,
        CashSale $result,
        CashSale $cashSale,
        InvoiceInterface $magentoInvoice,
        OrderInterface $magentoOrder
    ) {
        if ($this->taxConfig->getSkipTax()) {
            return $result;
        }

        $this->tax->getInvoiceExportTaxManager()->addTax($result, $magentoInvoice);

        return $result;
    }
}
