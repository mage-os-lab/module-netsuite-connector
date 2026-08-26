<?php

namespace MageOS\NetSuiteConnector\Core\Model;

use MageOS\NetSuiteConnector\Core\Model\ImportQueue\EntityProcessor;

class ImportQueueManager
{
    /**
     * @var array
     */
    protected $idsWithErrors;
    /**
     * @var array
     */
    protected $removeSuperLinks = [];
    /**
     * @var array
     */
    private $attributeInfo = [];
    /**
     * @var ImportRowList
     */
    private $importRowList;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory
     */
    private $attrCollectionFactory;
    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\Logger\Logger
     */
    private $logger;
    /**
     * @var Plugin\ImportExport\PluginState|PluginState
     */
    private $state;
    /**
     * @var Importer
     */
    private $importer;
    /**
     * @var array
     */
    private $importProcessors;

    /**
     * ImportQueueManager constructor.
     * @param Importer $importer
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attrCollectionFactory
     * @param Logger\Logger $logger
     * @param ImportRowList $importRowList
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param Plugin\ImportExport\PluginState $state
     * @param array $importProcessors
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Core\Model\Importer $importer,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attrCollectionFactory,
        \MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger,
        \MageOS\NetSuiteConnector\Core\Model\ImportRowList $importRowList,
        \Magento\Framework\App\ResourceConnection $resource,
        \MageOS\NetSuiteConnector\Core\Model\Plugin\ImportExport\PluginState $state,
        array $importProcessors = []
    ) {
        $this->importRowList = $importRowList;
        $this->resource = $resource;
        $this->attrCollectionFactory = $attrCollectionFactory;
        $this->logger = $logger;
        $this->state = $state;
        $this->importer = $importer;
        $this->importProcessors = $importProcessors;
    }

    /**
     * @param $rowList ImportRowList|null
     */
    public function import($rowList)
    {
        if ($rowList) {
            $this->importRowList->mergeWith($rowList);
        }
    }

    /**
     *
     */
    public function commit()
    {
        $this->idsWithErrors = [];

        // enable importexport plugins
        $this->state->setRunning(true);
        $importer = $this->importer;

        $entities = $this->importRowList->getEntities();

        $attributeInfo = $this->loadAttributeInfo();
        $this->importRowList->setAttributeInfo($attributeInfo);

        foreach ($entities as $entity) {
            // For debug purposes, we time the request
            $start = (float)array_sum(explode(' ', microtime()));
            $importer->setEntityCode($entity);
            $importer->setValidationRequired(true);

            $importProcessor = $this->getImportProcessor($entity);

            /**
             * We are still passing the $entity in as 'default' processor still needs to fetch correct rows (handling
             * multiple entities)
             */
            $idsWithErrors = $importProcessor->process($entity);
            if (!empty($idsWithErrors)) {
                $this->idsWithErrors += $idsWithErrors;
            }

            $end = (float)array_sum(explode(' ', microtime()));
            $this->logger->addDebug(
                sprintf('Processing time for %s: %0.4f s (Errors: %s)', $entity, ($end - $start), count($idsWithErrors))
            );
        }

        foreach ($entities as $entity) {
            $importProcessor = $this->getImportProcessor($entity);
            $importProcessor->postProcess($entity, ['superLinks' => $this->removeSuperLinks]);
        }
        $reindex = $this->getImportProcessor('catalog_product');
        $reindex->reindexAndCleanCache();
        $this->removeSuperLinks = [];

        $this->importRowList->clear();
    }

    /**
     * @param string $entity
     * @return EntityProcessor
     */
    private function getImportProcessor(string $entity): EntityProcessor
    {
        $entity = isset($this->importProcessors[$entity]) ? $entity : 'default';
        return $this->importProcessors[$entity];
    }

    /**
     * @param $netSuiteId
     * @return bool
     */
    public function isProductInQueue($netSuiteId)
    {
        return $this->importRowList->isProductInQueue($netSuiteId);
    }

    /**
     * Return netsuite ID list which failed to import
     * @return array
     */
    public function getFailedNetsuiteIds()
    {
        return $this->idsWithErrors;
    }

    /**
     * Resolve sku from the products in the existing rows
     * @param $netSuiteId
     * @return string|null
     */
    public function resolveSku($netSuiteId)
    {
        return $this->importRowList->resolveSku($netSuiteId);
    }

    /**
     * @param $netSuiteId
     * @return null
     */
    public function getProductRowById($netSuiteId)
    {
        return $this->importRowList->getProductRowById($netSuiteId);
    }

    /**
     * TODO: Since this is used ONLY in AllProducts import, it should be moved there
     *
     * @param $rows
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteProducts($rows)
    {
        if (empty($rows)) {
            return;
        }

        $this->importer->setEntityCode('catalog_product');
        $this->importer->deleteData($rows);
    }

    /**
     * TODO: Since this is used ONLY in AllProducts import, it should be moved there
     *
     * @return array
     */
    public function fetchAllSkuRows()
    {
        $connection = $this->resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
        $select = $connection->select()->from(
            ['cpe' => $connection->getTableName(MagentoTables::PRODUCT_ENTITY)],
            [
                'cpe.sku'
            ]
        );

        $rows = [];
        $stmt = $connection->query($select);
        while ($sku = $stmt->fetch()) {
            $rows[] = $sku;
        }

        return $rows;
    }

    /**
     * TODO: Add filtering to the collection as we are using only few attribute types
     * $attribute['type'] === 'select' && $attribute['is_global']
     * AND
     * $ttribute['type'] === 'text'
     * Also, why is this being handled in ImportQueueManager?!? Only answer I see - to keep ImportRowList clean from DI
     * so you can call it as "= new ImportRowList()"
     *
     * @return array
     */
    private function loadAttributeInfo()
    {
        if ($this->attributeInfo) {
            return $this->attributeInfo;
        }

        $attributeInfo = [];

        foreach ($this->attrCollectionFactory->create() as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            $attributeType = \Magento\ImportExport\Model\Import::getAttributeType($attribute);
            $attributeInfo[$attributeCode] = [
                'type' => $attributeType,
                'is_global' => $attribute->getIsGlobal(),
            ];
        }

        $this->attributeInfo = $attributeInfo;

        return $this->attributeInfo;
    }

    /**
     * @param $entity_ids
     * @param $parent_ids
     */
    public function queueSuperLinkForRemoval($entity_id, $parent_id)
    {
        $this->removeSuperLinks['entity_skus'][] = $entity_id;
        $this->removeSuperLinks['parent_skus'][] = $parent_id;
    }

    /**
     * TODO: See where its used. There is another task there, IMO we don't need this here
     * as the referenced class has access to its own "importRowList"
     *
     * @param $entity string
     * @param $row array
     */
    public function pushRowToEntity($entity, $row)
    {
        $this->importRowList->pushRowToEntity($entity, $row);
    }

    /**
     * TODO: Check if we can move it to the CustomFieldIdListPrefetcher class
     *
     * Returns internal ids mapped to SKUs: [internal_id => sku]
     * @param array $nsIds
     * @return array
     */
    public function mapIds(array $nsIds): array
    {
        $productMap = [];
        $fetched = [];

        foreach ($nsIds as $id) {
            if ($this->isProductInQueue($id)) {
                $fetched[] = $id;

                $product = $this->getProductRowById($id);
                if ($product['product_online'] === '1') {
                    $productMap[$id] = [
                        'name' => $product['name'] ?? '',
                        'sku' => $product['sku'],
                        'price' => $product['price'] ?? '',
                    ];
                    continue;
                }
            }
        }

        return $productMap;
    }
}
