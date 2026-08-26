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

namespace MageOS\NetSuiteConnector\Inventory\Model\NetSuiteInventoryRepository;

use NetSuite\Classes\ItemSearchAdvanced;
use NetSuite\Classes\ItemSearchRow;
use NetSuite\Classes\SearchMoreWithIdRequest;
use NetSuite\Classes\SearchRequest;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\CustomFieldAccess;
use MageOS\NetSuiteConnector\Inventory\Model\Config\Source\FieldType;
use RuntimeException;

/**
 * This class is responsible for stock data transformation before importing from NS.
 * @TODO refactor high coupling (15)
 * @suppressWarnings(PHPMD)
 */
abstract class AbstractTransformation implements StockDataTransformationInterface
{
    protected array $netSuiteIdMap = [];
    protected array $stockData = [];
    protected array $productIdsToReindex = [];
    protected \MageOS\NetSuiteConnector\Inventory\Model\Config\StockConfig $stockConfig;
    protected \Magento\Framework\Event\ManagerInterface $eventManager;
    protected \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry;
    protected \MageOS\NetSuiteConnector\Product\Model\ResourceModel\Repository $netsuiteProductRepository;
    protected \Magento\CatalogInventory\Api\StockConfigurationInterface $magentoStockConfiguration;

    public function __construct(
        \MageOS\NetSuiteConnector\Inventory\Model\Config\StockConfig $stockConfig,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \MageOS\NetSuiteConnector\Product\Model\ResourceModel\Repository $netsuiteProductRepository,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $magentoStockConfiguration
    ) {
        $this->stockConfig = $stockConfig;
        $this->eventManager = $eventManager;
        $this->stockRegistry = $stockRegistry;
        $this->netsuiteProductRepository = $netsuiteProductRepository;
        $this->magentoStockConfiguration = $magentoStockConfiguration;
    }

    /**
     * @inheritDoc
     */
    public function processSavedSearch($netsuiteService, $savedSearchId): array
    {
        $search = new ItemSearchAdvanced();
        $search->savedSearchId = $savedSearchId;

        $request = new SearchRequest();
        $request->searchRecord = $search;

        $searchResponse = $netsuiteService->search($request);
        $this->transformNetSuiteSearchResponse($searchResponse);

        //rest of the pages
        $totalPages = $searchResponse->searchResult->totalPages;
        $searchId = $searchResponse->searchResult->searchId;
        for ($i = 2; $i <= $totalPages; $i++) {

            $searchMoreRequest = new SearchMoreWithIdRequest();
            $searchMoreRequest->pageIndex = $i;
            $searchMoreRequest->searchId = $searchId;

            $searchResponse = $netsuiteService->searchMoreWithId($searchMoreRequest);
            $this->transformNetSuiteSearchResponse($searchResponse);
        }
        return [$this->stockData, $this->productIdsToReindex];
    }

    /**
     * Process NS search response with stock data
     *
     * @param $searchResponse
     * @throws RuntimeException
     */
    abstract protected function transformNetSuiteSearchResponse($searchResponse);

    /**
     * Retrieve QTY from given NS item
     *
     * It could be in different places depends on configuration and product type
     *
     * @param $itemSearchRow
     * @return int|null|float
     */
    protected function getQtyFromSearchRow($itemSearchRow)
    {
        static $fieldName = null;
        static $fieldType = null;
        static $stockStoredAtLocationLevel = null;

        if ($fieldName === null) {
            $fieldName = $this->stockConfig->getQtyFieldName();
            $fieldType = $this->stockConfig->getQtyFieldType();
            $stockStoredAtLocationLevel = $this->stockConfig->getStockStoredAtLocationLevel();
        }

        if ($stockStoredAtLocationLevel) {
            switch ($fieldType) {
                case FieldType::FIELD_TYPE_STANDARD:
                    return $this->extractFromStandardField($itemSearchRow, $fieldName);
                case FieldType::FIELD_TYPE_CUSTOM:
                    return CustomFieldAccess::get($itemSearchRow->basic, $fieldName);
            }
        }
        return CustomFieldAccess::get($itemSearchRow, $fieldName);
    }

    /**
     * Retrieve and prepare stock data for single NS item
     *
     * @param $itemSearchRow
     */
    abstract protected function getRowData($itemSearchRow);

    /**
     * @param $itemSearchRow
     * @param $fieldName
     * @return float
     */
    protected function extractFromStandardField($itemSearchRow, $fieldName): float
    {
        if (!empty($itemSearchRow->basic->{$fieldName}[0])
            && is_object($itemSearchRow->basic->{$fieldName}[0])) {
            return $itemSearchRow->basic->{$fieldName}[0]->searchValue;
        } elseif (is_object($itemSearchRow->memberItemJoin)
            && !empty($itemSearchRow->basic->{$fieldName}[0])
            && is_object($itemSearchRow->memberItemJoin->{$fieldName}[0])
        ) {
            return $itemSearchRow->memberItemJoin->{$fieldName}[0]->searchValue;
        }
        return 0.0;
    }

    /**
     * Load mapping between NS products and magento products by NS internal ID
     *
     * @param ItemSearchRow[] $records
     */
    protected function prepareProductIds(array $records): void
    {
        $netSuiteIds = [];
        foreach ($records as $record) {
            $netSuiteIds[] = $record->basic->internalId[0]->searchValue->internalId;
        }

        $this->netSuiteIdMap = $this->netsuiteProductRepository->mapNetSuiteIdsToProductIds($netSuiteIds);
    }
}
