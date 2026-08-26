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

namespace MageOS\NetSuiteConnector\Inventory\Single\Model\NetSuiteInventoryRepository;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use NetSuite\Classes\ItemType;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\ResponseValidator;
use MageOS\NetSuiteConnector\Inventory\Model\NetSuiteInventoryRepository\StockDataTransformationInterface;
use MageOS\NetSuiteConnector\Inventory\Model\Product\StockData;
use MageOS\NetSuiteConnector\Inventory\Model\NetSuiteInventoryRepository\AbstractTransformation;

/**
 * This class is responsible for stock data transformation before importing from NS.
 */
class StockDataTransformation extends AbstractTransformation implements StockDataTransformationInterface
{

    private array $defaultStockData = [
        'manage_stock' => 1,
        'use_config_manage_stock' => 1,
        'qty' => 0,
        'min_qty' => 0,
        'use_config_min_qty' => 1,
        'min_sale_qty' => 1,
        'use_config_min_sale_qty' => 1,
        'max_sale_qty' => 10000,
        'use_config_max_sale_qty' => 1,
        'is_qty_decimal' => 0,
        'backorders' => 0,
        'use_config_backorders' => 1,
        'notify_stock_qty' => 1,
        'use_config_notify_stock_qty' => 1,
        'enable_qty_increments' => 0,
        'use_config_enable_qty_inc' => 1,
        'qty_increments' => 0,
        'use_config_qty_increments' => 1,
        'is_in_stock' => 1,
        'low_stock_date' => null,
        'stock_status_changed_auto' => 0,
        'is_decimal_divided' => 0,
    ];

    /**
     * Process NS search response with stock data
     *
     * @param $searchResponse
     * @throws \MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException
     * @throws \MageOS\NetSuiteConnector\Core\Exception\NetSuiteRuntimeException
     */

    protected function transformNetSuiteSearchResponse($searchResponse): void
    {
        ResponseValidator::validate($searchResponse);

        /**
         * Typecasting since searchRow can be null
         */
        $records = (array)$searchResponse->searchResult->searchRowList->searchRow;
        $this->prepareProductIds($records);

        foreach ($records as $record) {
            $this->prepareSingleInventoryUpdate($record);
        }
    }

    /**
     * Retrieve stock data from NS item and push it into class variable
     *
     * Left this method as public for testing purposes
     *
     * @param $itemSearchRow
     * @return array
     */
    public function prepareSingleInventoryUpdate($itemSearchRow): array
    {
        $internalId = $itemSearchRow->basic->internalId[0]->searchValue->internalId;

        if (!isset($this->netSuiteIdMap[$internalId])) {
            // item not imported
            return [];
        }

        $row = [];

        $row['product_id'] = $this->netSuiteIdMap[$internalId]['entity_id'];
        $row['website_id'] = $this->magentoStockConfiguration->getDefaultScopeId();
        $row['stock_id'] = $this->stockRegistry->getStock($row['website_id'])->getStockId();

        $stockItemDo = $this->stockRegistry->getStockItem($row['product_id'], $row['website_id']);
        $existStockData = $stockItemDo->getData();

        $rowData = $this->getRowData($itemSearchRow) ?? [];

        $row = array_merge(
            $this->defaultStockData,
            array_intersect_key($existStockData, $this->defaultStockData),
            array_intersect_key($rowData, $this->defaultStockData),
            $row
        );

        $stockData = new StockData($row, $itemSearchRow->basic);

        $this->eventManager->dispatch('netsuite_stock_item_save_before', ['stock_data' => $stockData]);

        if (!isset($this->stockData[$rowData['sku']])) {
            $this->stockData[$rowData['sku']] = $stockData->getStockItem();
            $this->productIdsToReindex[] = $row['product_id'];
        } else {
            $this->stockData[$rowData['sku']]['qty'] = (float)$this->stockData[$rowData['sku']]['qty']
                + (float)$stockData->getStockItem()['qty'];
            if (!$this->stockData[$rowData['sku']]['is_in_stock']) {
                $this->stockData[$rowData['sku']]['is_in_stock'] = $stockData->getStockItem()['is_in_stock'];
            }
        }

        return $row;
    }

    /**
     * Retrieve and prepare stock data for single NS item
     *
     * @param $itemSearchRow
     * @return array|null
     */
    protected function getRowData($itemSearchRow): ?array
    {
        $internalId = $itemSearchRow->basic->internalId[0]->searchValue->internalId;
        if (!$internalId) {
            return null;
        }

        $row = [
            'sku' => $this->netSuiteIdMap[$internalId]['sku']
        ];

        $magentoProductId = $this->netSuiteIdMap[$internalId]['entity_id'];
        $magentoProductType = $this->netSuiteIdMap[$internalId]['type_id'];
        if (!$magentoProductId) {
            return $row;
        }
        if ($magentoProductType === Configurable::TYPE_CODE) {
            $row['use_config_manage_stock'] = 0;
            $row['is_in_stock'] = 1;
            $row['manage_stock'] = 0;
        } elseif (!empty($itemSearchRow->basic->type[0])
            && is_object($itemSearchRow->basic->type[0])
            && $itemSearchRow->basic->type[0]->searchValue === ItemType::_nonInventoryItem
        ) {
            $row['use_config_manage_stock'] = 0;
            $row['is_in_stock'] = 1;
            $row['manage_stock'] = 0;
        } else {
            $newQty = $this->getQtyFromSearchRow($itemSearchRow);
            $row['qty'] = $newQty;

            if ($this->stockConfig->getChangeStockStatusUnderZero()) {
                $row['is_in_stock'] = $newQty > 0 ? 1 : 0;
            } else {
                $row['is_in_stock'] = 1;
            }

            $row['use_config_manage_stock'] = 1;
        }

        return $row;
    }
}
