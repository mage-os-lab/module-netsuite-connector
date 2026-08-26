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

namespace MageOS\NetSuiteConnector\Tax\Api;

use MageOS\NetSuiteConnector\Tax\Model\Order\Export\TaxManagerInterface as OrderExportTaxInterface;
use MageOS\NetSuiteConnector\Tax\Model\Invoice\Export\TaxManagerInterface as InvoiceExportTaxInterface;

/**
 * Tax - interface that is responsible for the proper strategy setting.
 * It is an extension point for different strategies of tax mapping
 */
interface TaxInterface
{
    /**
     * retrieve Order Export Tax Manager Based on set Tax Handling Logic
     * for different scenarios
     * @return OrderExportTaxInterface
     */
    public function getOrderExportTaxManager(): OrderExportTaxInterface;

    /**
     * retrieve Invoice Export Tax Manager Based on set Tax Handling Logic
     * for different scenarios
     * @return InvoiceExportTaxInterface
     */
    public function getInvoiceExportTaxManager(): InvoiceExportTaxInterface;
}
