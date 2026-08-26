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

namespace MageOS\NetSuiteConnector\Tax\Plugin;

class OrderAddTax
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
     * @param \MageOS\NetSuiteConnector\Order\Model\Mapper\Order $subject
     * @param \NetSuite\Classes\SalesOrder $result
     * @param \Magento\Sales\Api\Data\OrderInterface $magentoOrder
     * @return \NetSuite\Classes\SalesOrder
     *
     * @suppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetNetsuiteFormat(
        \MageOS\NetSuiteConnector\Order\Model\Mapper\Order $subject,
        \NetSuite\Classes\SalesOrder $result,
        \Magento\Sales\Api\Data\OrderInterface $magentoOrder
    ): \NetSuite\Classes\SalesOrder {
        if ($this->taxConfig->getSkipTax()) {
            return $result;
        }

        $this->tax->getOrderExportTaxManager()->addTax($result, $magentoOrder);

        return $result;
    }
}
