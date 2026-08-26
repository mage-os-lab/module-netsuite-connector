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

namespace MageOS\NetSuiteConnector\Tax\Model;

use MageOS\NetSuiteConnector\Core\Exception\ConfigurationException;
use MageOS\NetSuiteConnector\Tax\Model\Order\Export\TaxManagerInterface as OrderExportTaxInterface;
use MageOS\NetSuiteConnector\Tax\Model\Invoice\Export\TaxManagerInterface as InvoiceExportTaxInterface;

/**
 * Tax - class that is responsible for the proper strategy setting.
 * It is an extension point for different strategies of tax mapping
 */
class Tax implements \MageOS\NetSuiteConnector\Tax\Api\TaxInterface
{
    /**
     * @var \MageOS\NetSuiteConnector\Tax\Model\Order\Export\TaxManagerInterface[]
     */
    private $orderTaxManagers;
    /**
     * @var \MageOS\NetSuiteConnector\Tax\Model\Invoice\Export\TaxManagerInterface[]
     */
    private $invoiceTaxManagers;
    /**
     * @var \MageOS\NetSuiteConnector\Tax\Model\Config\Tax
     */
    private $taxConfig;

    /**
     * TaxProcessor constructor.
     * @param Config\Tax $taxConfig
     * @param array $orderTaxManagers
     * @param array $invoiceTaxManagers
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Tax\Model\Config\Tax $taxConfig,
        array $orderTaxManagers = [],
        array $invoiceTaxManagers = []
    ) {
        $this->orderTaxManagers = $orderTaxManagers;
        $this->taxConfig = $taxConfig;
        $this->invoiceTaxManagers = $invoiceTaxManagers;
    }

    /**
     * @inheritDoc
     */
    public function getOrderExportTaxManager(): OrderExportTaxInterface
    {
        $taxLogic = $this->taxConfig->getTaxLogic();
        if (!isset($this->orderTaxManagers[$taxLogic])) {
            throw new ConfigurationException(
                'There is no Tax Logic set for order_export tax manager. Please check NSC configuration.'
            );
        }
        return $this->orderTaxManagers[$taxLogic];
    }

    /**
     * @inheritDoc
     */
    public function getInvoiceExportTaxManager(): InvoiceExportTaxInterface
    {
        $taxLogic = $this->taxConfig->getTaxLogic();
        if (!isset($this->invoiceTaxManagers[$taxLogic])) {
            throw new ConfigurationException(
                'There is no Tax Logic set for invoice_export tax manager. Please check NSC configuration.'
            );
        }
        return $this->invoiceTaxManagers[$taxLogic];
    }
}
