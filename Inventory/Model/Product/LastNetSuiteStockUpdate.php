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

namespace MageOS\NetSuiteConnector\Inventory\Model\Product;

class LastNetSuiteStockUpdate
{
    private \Magento\Catalog\Model\ResourceModel\Product\Action $productAction;
    private \Magento\Framework\Stdlib\DateTime\DateTime $dateTime;

    public function __construct(
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Catalog\Model\ResourceModel\Product\Action $productAction
    ) {
        $this->productAction = $productAction;
        $this->dateTime = $dateTime;
    }

    /**
     * Update all products with current timestamp of when stock got updated.
     * @param array $productIds
     * @throws \Exception
     */
    public function execute(array $productIds): void
    {
        if (count($productIds) == 0) {
            return;
        }

        $timestamp = $this->dateTime->gmtDate();
        $this->productAction->updateAttributes($productIds, ['last_netsuite_stock_update' => $timestamp], '');
    }
}
