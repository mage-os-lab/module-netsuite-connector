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

namespace MageOS\NetSuiteConnector\Tax\Model\Invoice\Export;

use Magento\Sales\Api\Data\InvoiceInterface;
use NetSuite\Classes\CashSale;

/**
 * Invoice Export Tax Manager tax management business logic
 * Used during Magento Invoice Export to NetSuite.
 *
 * @spi
 */
interface TaxManagerInterface
{

    /**
     * Add Taxes information to the NetSuite CacheSale
     *
     * @param CashSale $cashSale
     * @param InvoiceInterface $magentoInvoice
     */
    public function addTax(CashSale $cashSale, InvoiceInterface $magentoInvoice): void;
}
