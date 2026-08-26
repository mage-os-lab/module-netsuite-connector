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
namespace MageOS\NetSuiteConnector\Product\Model\ImportQueue\Processor;

use MageOS\NetSuiteConnector\Core\Exception\ConnectorRuntimeException;
use MageOS\NetSuiteConnector\Core\Model\ImportQueue\EntityProcessor;
use MageOS\NetSuiteConnector\Core\Model\MagentoTables;
use MageOS\NetSuiteConnector\Product\Model\EntityIdColumn;

class CatalogProduct extends EntityProcessor
{
    public function __construct(
        private \MageOS\NetSuiteConnector\Product\Model\Product\Import\UrlCollisionValidator $urlCollisionValidator,
        private \MageOS\NetSuiteConnector\Product\Model\ResourceModel\Repository $netsuiteProductRepository,
        protected \Magento\Framework\App\ResourceConnection $resourceConnection,
        private \Magento\Framework\App\CacheInterface $cacheManager,
        private \Magento\Framework\App\Cache\StateInterface $cacheState,
        private \Magento\Framework\App\Cache\Type\FrontendPool $cachePool,
        private \Magento\Framework\Indexer\CacheContext $cacheContext,
        private \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        private \Magento\Framework\Event\Manager $eventManager,
        protected \MageOS\NetSuiteConnector\Core\Model\FlatIndexState $flatIndexState,
        protected \MageOS\NetSuiteConnector\Core\Model\ImportRowList $importRowList,
        protected \MageOS\NetSuiteConnector\Core\Model\Importer $importer,
        protected \MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger,
        /** Saving product IDs that wre touched in the process to reindex them only */
        private array $affectedIds = [],
        private array $indexers = []
    ) {
        parent::__construct($resourceConnection, $flatIndexState, $importRowList, $importer, $logger);
    }

    public function process(string $entity): array
    {
        list($rawRows, $rows) = $this->preprocessCatalogProduct($entity);

        $skusWithErrors = [];
        // perform validation and return if general error occured
        if (!empty($rows)) {
            $this->importer->setValidationRequired(false);

            if (!$this->importer->validateData($rows)) {
                // @var ProcessingErrorAggregatorInterface $errorAggregator
                $errorAggregator = $this->importer->getErrorAggregator();
                $skusWithErrors = $this->validateProductErrors($errorAggregator, $rawRows);
            }
        }

        $this->preprocessRows($rawRows, $skusWithErrors);
        $idsWithErrors = parent::process($entity);
        $this->prepareReindexData($rows);
        return $idsWithErrors;
    }

    public function postProcess(string $entity, array $data = []): void
    {
        parent::postProcess($entity, $data);
        $this->removeSuperLinks($data['superLinks']);
    }

    private function preprocessCatalogProduct(string $entity): array
    {
        $rawRows = $this->importRowList->getRawEntityData('catalog_product');
        $rawRows = $this->transformBundleItems($rawRows);
        $rawRows = $this->urlCollisionValidator->validate($rawRows);
        $this->importRowList->setRawEntityData('catalog_product', $rawRows);
        $rows = $this->importRowList->getEntityRows($entity);
        return [$rawRows, $rows];
    }

    private function preprocessRows(array $rows, array $skusWithErrors): void
    {
        $netSuiteToSku = [];

        foreach ($rows as $row) {
            $sku = $row['sku'];
            if (!isset($skusWithErrors[$sku]) && isset($row['netsuite_internal_id'])) {
                $netSuiteToSku[$row['netsuite_internal_id']] = $sku;
            }
        }

        $this->updateSkus($netSuiteToSku);
    }

    private function validateProductErrors($errorAggregator, array $rawRows): array
    {
        if ($errorAggregator->hasToBeTerminated()) {
            throw new ConnectorRuntimeException('Error limit exceeded');
        }

        $skusWithErrors = [];
        $errors = $errorAggregator->getAllErrors();
        if (!empty($errors)) {
            list($idsWithErrors, $skusWithErrors) = $this->validateErrors($errors, $rawRows);
            unset($idsWithErrors);
        }

        return $skusWithErrors;
    }

    private function prepareReindexData(array $rows): void
    {
        $this->fixConfigurableTypes($rows);
        $affectedIds = $this->fetchAffectedProductIds($rows);
        $this->affectedIds =  array_unique(array_merge($this->affectedIds, $affectedIds));
    }

    public function reindexAndCleanCache(): void
    {
        $globalAffectedIds = $this->affectedIds;
        if (empty($globalAffectedIds)) {
            return;
        }
        $this->reindexRows($globalAffectedIds);
        // Note: clean_cache_by_tags is also called in reindexRows trace
        $this->clearProductCache($globalAffectedIds);
    }

    private function reindexRows(array $productIds): void
    {
        if (!$productIds) {
            return;
        }

        $this->initIndexers();

        if (!empty($productIds)) {
            $productIds = array_merge($this->fetchRelatedProducts($productIds), $productIds);

            /** @var \Magento\Framework\Indexer\IndexerInterface $indexer */
            foreach ($this->indexers as $indexer) {
                $retries = 0;

                while ($retries < 2) {
                    try {
                        $indexer->reindexList($productIds);
                        break;
                    } catch (\Exception $e) {
                        $this->logger->addInfo('Got exception. sleeping and retrying:' . $e->getMessage());
                        // phpcs:ignore
                        sleep(1);
                        $retries++;
                    }
                }

                if ($retries === 2) {
                    $this->logger->addError(sprintf('Indexer %s did not reindex.', $indexer->getId()));
                }
            }
        }
    }

    private function initIndexers(): void
    {
        if (!$this->indexers) {
            // TODO: do we really need to reindex ALL this?!?!?
            $indexerNames = [
                'catalog_product_category',
                'catalog_category_product',
                'catalog_product_attribute',
                'catalog_product_price',
                'cataloginventory_stock',
                'catalogrule_product',
                'catalogrule_product'
            ];

            $this->indexers = [];
            foreach ($indexerNames as $name) {
                try {
                    $this->indexers[] = $this->indexerRegistry->get($name);
                } catch (\Exception $e) {
                    $this->logger->addError($e->getMessage());
                }
            }
        }
    }

    /**
     * Selects all child product ids
     */
    private function fetchRelatedProducts(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $fieldName = EntityIdColumn::get();
        $related = $connection->fetchCol(
            $connection->select()->from(
                ['l' => $connection->getTableName(MagentoTables::PRODUCT_SUPER_LINK)],
                ['product_id']
            )
                ->joinInner(
                    ['p' => $connection->getTableName(MagentoTables::PRODUCT_ENTITY)],
                    'p.' . $fieldName . ' = l.parent_id',
                    []
                )
                ->joinInner(
                    ['e' => $connection->getTableName(MagentoTables::PRODUCT_ENTITY)],
                    'e.entity_id = l.product_id',
                    []
                )
                ->where('p.entity_id IN(?)', $productIds)
        );

        return array_diff($related, $productIds);
    }

    private function updateSkus(array $netSuiteToSku): void
    {
        if (empty($netSuiteToSku)) {
            return;
        }

        $fieldName = EntityIdColumn::get();
        $skuMap = $this->netsuiteProductRepository->mapNetSuiteIdsToProductIds(array_keys($netSuiteToSku), $fieldName);
        $mismatchedSkus = [];

        foreach ($netSuiteToSku as $nsId => $sku) {
            if (isset($skuMap[$nsId]) && $skuMap[$nsId]['sku'] !== $sku) {
                $mismatchedSkus[] = [
                    $fieldName => $skuMap[$nsId][$fieldName],
                    'sku' => $sku
                ];
            }
        }

        if (!empty($mismatchedSkus)) {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $connection->getTableName(MagentoTables::PRODUCT_ENTITY);

            $connection->insertOnDuplicate(
                $tableName,
                $mismatchedSkus,
                ['sku']
            );
        }
    }

    /**
     * Transforms bundle items according to:
     */
    public function transformBundleItems(array $rows): array
    {
        $bundleGroups = [];

        foreach ($rows as $row) {
            if (isset($row['_incomplete'])) {
                continue;
            }

            if ($row['product_type'] === 'bundle') {
                $bundleParentId = $row['bundle_parent_id'];
                unset($row['bundle_parent_id']);

                $bundleGroups[$bundleParentId] = $bundleGroups[$bundleParentId] ?? [];
                $bundleGroups[$bundleParentId][] = $row;

                $this->bundleSkus[$row['sku']] = $bundleParentId;
            }
        }

        $bundleLinks = [];
        $existingOptionSkus = [];
        foreach ($bundleGroups as $bundleParentId => $group) {
            $bundleOptions = [];
            foreach ($group as $bundleItem) {
                $options = $bundleItem['bundle_values'];

                foreach ($options as $option) {
                    $option['can_change_quantity'] = '0';
                    $sku = $option['sku'];

                    if (!isset($existingOptionSkus[$sku])) {
                        $existingOptionSkus[$sku] = 1;
                        $bundleOptions[] = $option;

                        $bundleLinks[] = [
                            'sku' => $row['sku'],
                            'option_sku' => $sku,
                            'netsuite_internal_id' => $bundleItem['netsuite_internal_id'],
                        ];
                    }
                }
            }
        }

        $this->importRowList->pushRowsToEntity('bundle_links', $bundleLinks);
        return $rows;
    }

    /**
     * Remove links from configurable to child products
     */
    public function removeSuperLinks(array $superLinks): void
    {
        if (!isset($superLinks['entity_skus'], $superLinks['parent_skus'])) {
            return;
        }

        $fieldName = EntityIdColumn::get();
        $connection = $this->resourceConnection->getConnection();
        $linkTable = $connection->getTableName(MagentoTables::PRODUCT_SUPER_LINK);

        $quotedSkus = $connection->quoteInto('IN (?)', array_unique($superLinks['entity_skus']));
        $quotedParentSkus = $connection->quoteInto('IN (?)', array_unique($superLinks['parent_skus']));

        $relatedIds = $connection->fetchPairs(
            $connection->select()->from(
                ['l' => $linkTable],
                ['l.link_id', 'l.parent_id']
            )
                ->joinInner(
                    ['p' => $connection->getTableName(MagentoTables::PRODUCT_ENTITY)],
                    'p.' . $fieldName . ' = l.parent_id',
                    []
                )
                ->joinInner(
                    ['e' => $connection->getTableName(MagentoTables::PRODUCT_ENTITY)],
                    'e.entity_id = l.product_id',
                    []
                )
                ->where(
                    "p.sku {$quotedParentSkus} AND e.sku {$quotedSkus}"
                )
        );

        if (!empty($relatedIds)) {
            $childIds = array_keys($relatedIds);
            $parentIds = array_unique(array_values($relatedIds));

            $quoted = $connection->quoteInto('IN (?)', $childIds);

            $connection->delete(
                $linkTable,
                "link_id {$quoted}"
            );

            $quotedParents = $connection->quoteInto('IN (?)', $parentIds);

            $connection->delete(
                $connection->getTableName(MagentoTables::PRODUCT_RELATIONS),
                "child_id {$quoted} AND parent_id {$quotedParents}"
            );
        }
    }

    /**
     * Configurable products without children become simple
     * we need to fix it
     * TODO for some reason, I recall this not actaully working as you still have a Simple Product at the end.
     * Is this still needed?
     */
    private function fixConfigurableTypes(array $rows): void
    {
        $skusConfigurable = [];
        foreach ($rows as $row) {
            if ($row['product_type'] === 'configurable') {
                $skusConfigurable[] = $row['sku'];
            }
        }

        $connection = $this->resourceConnection->getConnection(
            \Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION
        );

        $configurables = $connection->fetchPairs(
            $connection->select()->from(
                ['cpe' => $connection->getTableName(MagentoTables::PRODUCT_ENTITY)],
                ['cpe.entity_id', 'cpe.type_id']
            )->where('sku IN(?)', $skusConfigurable)
        );

        $updateIds = [];
        foreach ($configurables as $entity_id => $type_id) {
            if ($type_id !== 'configurable') {
                $updateIds[] = $entity_id;
            }
        }

        if (!empty($updateIds)) {
            $connection->update(
                $connection->getTableName(MagentoTables::PRODUCT_ENTITY),
                ['type_id' => 'configurable'],
                ['entity_id IN(?)' => $updateIds]
            );
        }
    }

    private function fetchAffectedProductIds(array $rows): array
    {
        if (!$rows) {
            return [];
        }

        $skusToFetch = [];
        foreach ($rows as $row) {
            if (isset($row['netsuite_internal_id']) && !isset($this->idsWithErrors[$row['netsuite_internal_id']])) {
                $skusToFetch[] = $row['sku'];
            }
        }

        $productIds = $this->fetchEntityIdsBySkus($skusToFetch);

        $mergedIds = array_merge(
            $productIds,
            $this->fetchParentProducts($productIds)
        );

        return array_unique($mergedIds);
    }

    public function fetchEntityIdsBySkus(array $skus): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->from(
            ['cpe' => $connection->getTableName(MagentoTables::PRODUCT_ENTITY)],
            [
                'cpe.entity_id'
            ]
        )->where('sku IN(?)', $skus);

        $rows = [];
        $stmt = $connection->query($select);
        while ($sku = $stmt->fetch()) {
            $rows[] = $sku['entity_id'];
        }
        return $rows;
    }

    /**
     * Selects all parent product ids
     */
    private function fetchParentProducts(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        $fieldName = EntityIdColumn::get();
        $connection = $this->resourceConnection->getConnection();
        $related = $connection->fetchCol(
            $connection->select()->from(
                ['l' => $connection->getTableName(MagentoTables::PRODUCT_SUPER_LINK)],
                []
            )
                ->joinInner(
                    ['p' => $connection->getTableName(MagentoTables::PRODUCT_ENTITY)],
                    'p.' . $fieldName . ' = l.parent_id',
                    ['p.entity_id']
                )
                ->where('l.product_id IN(?)', $productIds)
        );

        return array_diff($related, $productIds);
    }

    /**
     * TODO: This feel waaay to complicated to get cache flushed. Coupling to 5 classes?!?!?
     *
     * @param array $productIds
     */
    private function clearProductCache(array $productIds): void
    {
        if (empty($productIds)) {
            return;
        }

        $cacheList = [
            \Magento\Framework\App\Cache\Type\Block::TYPE_IDENTIFIER,
            \Magento\Framework\App\Cache\Type\Collection::TYPE_IDENTIFIER,
        ];

        $tags = ['catalog_product'];
        foreach ($productIds as $productId) {
            $tag = 'catalog_product_' . $productId;
            $tags[] = $tag;
        }

        $this->cacheManager->clean($tags);

        foreach ($cacheList as $cacheType) {
            if ($this->cacheState->isEnabled($cacheType)) {
                $this->cachePool->get($cacheType)->clean(
                    \Zend_Cache::CLEANING_MODE_MATCHING_TAG,
                    $tags
                );
            }
        }

        $this->cacheContext->registerTags($tags);
        $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $this->cacheContext]);
    }
}
