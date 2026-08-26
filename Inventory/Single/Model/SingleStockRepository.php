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
 */
namespace MageOS\NetSuiteConnector\Inventory\Single\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\Data\StockStatusInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * Class SingleStockRepository - this class encapsulates logic for single stock updating with compatible way
 * (for cases with one default stock)
 */
class SingleStockRepository implements \MageOS\NetSuiteConnector\Inventory\Model\MagentoInventoryRepositoryInterface
{
    private \Magento\CatalogInventory\Model\Indexer\Stock\Processor $indexerStock;
    private \Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory $stockResItemFactory;
    private \Magento\Framework\App\ResourceConnection $resource;
    private \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder;
    private \Magento\InventoryApi\Api\SourceItemRepositoryInterface $sourceItemRepository;
    private \Magento\Inventory\Model\ResourceModel\SourceItem\SaveMultiple $saveMultipleResource;
    private ?\Magento\Framework\DB\Adapter\AdapterInterface $connection = null;
    private \Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface $getSkusByIds;
    private \Magento\Framework\EntityManager\MetadataPool $metadataPool;

    /**
     * SingleStockRepository constructor.
     * @param \Magento\CatalogInventory\Model\Indexer\Stock\Processor $indexerStock
     * @param \Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory $stockResItemFactory
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\InventoryApi\Api\SourceItemRepositoryInterface $sourceItemRepository
     * @param \Magento\Inventory\Model\ResourceModel\SourceItem\SaveMultiple $saveMultipleResource
     * @param \Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface $getSkusByIds
     * @param \Magento\Framework\EntityManager\MetadataPool $metadataPool;
     */
    public function __construct(
        \Magento\CatalogInventory\Model\Indexer\Stock\Processor $indexerStock,
        \Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory $stockResItemFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\InventoryApi\Api\SourceItemRepositoryInterface $sourceItemRepository,
        \Magento\Inventory\Model\ResourceModel\SourceItem\SaveMultiple $saveMultipleResource,
        \Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface $getSkusByIds,
        \Magento\Framework\EntityManager\MetadataPool $metadataPool
    ) {
        $this->indexerStock = $indexerStock;
        $this->stockResItemFactory = $stockResItemFactory;
        $this->resource = $resource;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sourceItemRepository = $sourceItemRepository;
        $this->saveMultipleResource = $saveMultipleResource;
        $this->getSkusByIds = $getSkusByIds;
        $this->metadataPool = $metadataPool;
    }

    /**
     * @inheritDoc
     */
    public function saveInventoryData(array $stockData, array $productIdsToReindex): bool
    {
        if (!empty($stockData)) {
            //cataloginventory
            $this->processStockStatus($stockData);

            //MSI(default stock) table data updating via SQL
            $sourceItemsForUpdate = $this->getDefaultForUpdate($stockData);
            if (!empty($sourceItemsForUpdate)) {
                $this->saveMultipleResource->execute($sourceItemsForUpdate);

            }
        }
        //price index reindex
        if (!empty($productIdsToReindex)) {
            $this->indexerStock->reindexList($productIdsToReindex);
        }
        return true;
    }

    /**
     * method looks for SourceItems of the products in the stockData, updates their qty and status and return for saving
     * @param array $stockData
     * @return array
     */
    private function getDefaultForUpdate(array $stockData): array
    {
        $transformedStockData = $this->transformStockData($stockData);
        $skus = array_keys($transformedStockData);
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, $skus, 'in')
            ->addFilter(SourceItemInterface::SOURCE_CODE, 'default')
            ->create();
        $result = $this->sourceItemRepository->getList($searchCriteria)->getItems();
        foreach ($result as $sourceItem) {
            $sku = $sourceItem->getData('sku');
            if (isset($transformedStockData[$sku])
                && isset($transformedStockData[$sku]['qty'])
                && isset($transformedStockData[$sku]['is_in_stock'])) {

                $sourceItem->setQuantity((float)sprintf("%.4f", $transformedStockData[$sku]['qty']));
                $sourceItem->setStatus((int)$transformedStockData[$sku]['is_in_stock']);
            }
        }
        return $result;
    }

    public function processStockStatus(array $stockData): void
    {
        //legacy table data updating via SQL
        /** @var \Magento\CatalogInventory\Model\ResourceModel\Stock\Item $stockResource */
        $stockResource = $this->stockResItemFactory->create();
        $entityTable = $stockResource->getMainTable();
        $this->resource->getConnection()->insertOnDuplicate($entityTable, array_values($stockData));

        foreach ($stockData as $stockItem) {
            $ids = [];
            $status = !$stockItem['use_config_manage_stock'] && !$stockItem['manage_stock'];
            $status = (int)($status ?: $stockItem['is_in_stock']);
            $ids[] = $stockItem['product_id'];
            //update parent status if needs
            $parentId = $this->getParentId((int)$stockItem['product_id']);
            if ($status && $parentId > 0) {
                $this->updateIsInStockStatus($parentId);
                $ids[] = $parentId;
            }

            //update item status
            $this->updateStockStatus(
                $status,
                $ids,
                (int)$stockItem['website_id']
            );
        }
    }

    private function getParentId(int $childId): int
    {
        $connection = $this->getConnection();
        $tableName = $this->resource->getTableName('catalog_product_relation');
        $select = $connection->select()
            ->from(
                ['cpr' => $tableName],
                []
            )
            ->where('cpr.child_id = (?)', $childId);
        $idField = $this->metadataPool
            ->getMetadata(ProductInterface::class)
            ->getLinkField();
        if ($idField === 'entity_id') {
            $select->columns('cpr.parent_id');
        } elseif ($idField === 'row_id') {
            $select->join(
                ['cpe' => $this->resource->getTableName('catalog_product_entity')],
                'cpe.row_id = cpr.parent_id',
                ['entity_id']
            );
        }
        return (int)$connection->fetchOne($select);
    }

    private function updateIsInStockStatus(int $productId): void
    {
        $connection = $this->getConnection();
        $tableName = $this->resource->getTableName('cataloginventory_stock_item');
        $connection->update(
            $tableName,
            [StockItemInterface::IS_IN_STOCK => 1],
            [StockItemInterface::PRODUCT_ID . ' = ?' => $productId]
        );
    }

    private function updateStockStatus(int $status, array $productIds, int $websiteId): void
    {
        $connection = $this->getConnection();
        $tableName = $this->resource->getTableName('cataloginventory_stock_status');
        $connection->update(
            $tableName,
            [StockStatusInterface::STOCK_STATUS => $status],
            [
                StockStatusInterface::PRODUCT_ID . ' IN (?)' => $productIds,
                'website_id = ?' => $websiteId,
            ]
        );
    }

    private function getConnection(): AdapterInterface
    {
        if (!$this->connection) {
            $this->connection = $this->resource->getConnection();
        }
        return $this->connection;
    }

    /**
     * Transform stock data for MSI modules
     *
     * @param array $stockData
     * @return array
     */
    private function transformStockData($stockData)
    {
        $newStockData = [];
        $ids = array_column($stockData, 'product_id');
        $skus = $this->getSkusByIds->execute($ids);
        foreach ($stockData as $stockRow) {
            $sku = array_key_exists($stockRow['product_id'], $skus) ? $skus[$stockRow['product_id']] : false;
            if ($sku === false) {
                // This shouldn't be in use because SKU must exist always
                // Added not to break whole process in case of invalid DB data
                continue;
            }
            $newStockData[$sku] = [
                'qty' => $stockRow['qty'],
                'is_in_stock' => $stockRow['is_in_stock'],
            ];
        }
        return $newStockData;
    }
}
