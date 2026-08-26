<?php declare(strict_types=1);
/*
 *   RocketWeb
 *
 *  NOTICE OF LICENSE
 *
 *  This source file is subject to the Open Software License (OSL 3.0)
 *  that is bundled with this package in the file LICENSE.txt.
 *  It is also available through the world-wide-web at this URL:
 *  http://opensource.org/licenses/osl-3.0.php
 *
 *  @category  RocketWeb
 *  @package   MageOS_NetSuiteConnector
 *  @copyright Copyright (c) 2026 RocketWeb (http://rocketweb.com)
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  @author    Rocket Web Inc.
 *
 *
 */

namespace MageOS\NetSuiteConnector\Inventory\Model\Product;

use NetSuite\Classes\ItemSearchRowBasic;

/**
 * This class is a data object for stock data. It is used for incremental collecting of stock data during import
 */
class StockData
{
    private array $stockItem;
    private \NetSuite\Classes\ItemSearchRowBasic $itemSearchRow;

    /**
     * StockData constructor.
     *
     * @param array $stockItem
     * @param \NetSuite\Classes\ItemSearchRowBasic $itemSearchRow
     */
    public function __construct(
        array $stockItem,
        \NetSuite\Classes\ItemSearchRowBasic $itemSearchRow
    ) {
        $this->stockItem = $stockItem;
        $this->itemSearchRow = $itemSearchRow;
    }

    /**
     * @return array
     */
    public function getStockItem(): array
    {
        return $this->stockItem;
    }

    /**
     * @param array $stockItem
     */
    public function setStockItem(array $stockItem)
    {
        $this->stockItem = $stockItem;
    }

    /**
     * @return ItemSearchRowBasic
     */
    public function getItemSearchRow(): ItemSearchRowBasic
    {
        return $this->itemSearchRow;
    }

    /**
     * @param ItemSearchRowBasic $itemSearchRow
     */
    public function setItemSearchRow(ItemSearchRowBasic $itemSearchRow)
    {
        $this->itemSearchRow = $itemSearchRow;
    }
}
