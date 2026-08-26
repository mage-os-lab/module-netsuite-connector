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

// @codingStandardsIgnoreFile
namespace MageOS\NetSuiteConnector\Product\Model\Product\Import;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\Config as CatalogConfig;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogImportExport\Model\Import\Product as CoreImportProduct;
use Magento\CatalogImportExport\Model\Import\Product\RowValidatorInterface as ValidatorInterface;
use Magento\CatalogImportExport\Model\Import\Product\SkuStorage;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Model\ResourceModel\Db\ObjectRelationProcessor;
use Magento\Framework\Model\ResourceModel\Db\TransactionManagerInterface;
use Magento\Framework\Stdlib\DateTime;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use MageOS\NetSuiteConnector\Core\Model\Plugin\ImportExport\PluginState;

/**
 * This class extends the core class so all warnings should be ignored.
 *
 * @SuppressWarnings(PHPMD)
 */
class Product extends CoreImportProduct
{
    /**
     * @var string
     */
    const EMPTY_POSITION = '300000';

    /**
     * @var string
     */
    const PAGE_LAYOUT_DEFAULT_PATH = 'web/default_layouts/default_product_layout';

    /**
     * @var string
     */
    const PAGE_LAYOUT_ATTR = 'page_layout';

    /**
     * @var CatalogConfig
     */
    private $catalogConfig;

    private \MageOS\NetSuiteConnector\Core\Model\Plugin\ImportExport\PluginState $state;

    private SkuStorage $skuStorage;

    /**
     * ProductImportExport constructor.
     * This obviously will be refactored later by Magento...
     * @param \MageOS\NetSuiteConnector\Core\Model\Plugin\ImportExport\PluginState $state
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\ImportExport\Helper\Data $importExportData
     * @param \Magento\ImportExport\Model\ResourceModel\Import\Data $importData
     * @param \Magento\Eav\Model\Config $config
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration
     * @param \Magento\CatalogInventory\Model\Spi\StockStateProviderInterface $stockStateProvider
     * @param \Magento\Catalog\Helper\Data $catalogData
     * @param Import\Config $importConfig
     * @param \Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory $resourceFactory
     * @param \Magento\CatalogImportExport\Model\Import\Product\OptionFactory $optionFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setColFactory
     * @param \Magento\CatalogImportExport\Model\Import\Product\Type\Factory $productTypeFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\LinkFactory $linkFactory
     * @param \Magento\CatalogImportExport\Model\Import\Proxy\ProductFactory $proxyProdFactory
     * @param \Magento\CatalogImportExport\Model\Import\UploaderFactory $uploaderFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory $stockResItemFac
     * @param DateTime\TimezoneInterface $localeDate
     * @param DateTime $dateTime
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
     * @param \Magento\CatalogImportExport\Model\Import\Product\StoreResolver $storeResolver
     * @param \Magento\CatalogImportExport\Model\Import\Product\SkuProcessor $skuProcessor
     * @param \Magento\CatalogImportExport\Model\Import\Product\CategoryProcessor $categoryProcessor
     * @param \Magento\CatalogImportExport\Model\Import\Product\Validator $validator
     * @param ObjectRelationProcessor $objectRelationProcessor
     * @param TransactionManagerInterface $transactionManager
     * @param \Magento\CatalogImportExport\Model\Import\Product\TaxClassProcessor $taxClassProcessor
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Catalog\Model\Product\Url $productUrl
     * @param array $data
     * @param array $dateAttrCodes
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Core\Model\Plugin\ImportExport\PluginState $state,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\ImportExport\Helper\Data $importExportData,
        \Magento\ImportExport\Model\ResourceModel\Import\Data $importData,
        \Magento\Eav\Model\Config $config,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Stdlib\StringUtils $string,
        ProcessingErrorAggregatorInterface $errorAggregator,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration,
        \Magento\CatalogInventory\Model\Spi\StockStateProviderInterface $stockStateProvider,
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\ImportExport\Model\Import\Config $importConfig,
        \Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory $resourceFactory,
        \Magento\CatalogImportExport\Model\Import\Product\OptionFactory $optionFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setColFactory,
        \Magento\CatalogImportExport\Model\Import\Product\Type\Factory $productTypeFactory,
        \Magento\Catalog\Model\ResourceModel\Product\LinkFactory $linkFactory,
        \Magento\CatalogImportExport\Model\Import\Proxy\ProductFactory $proxyProdFactory,
        \Magento\CatalogImportExport\Model\Import\UploaderFactory $uploaderFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory $stockResItemFac,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        DateTime $dateTime,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\CatalogImportExport\Model\Import\Product\StoreResolver $storeResolver,
        \Magento\CatalogImportExport\Model\Import\Product\SkuProcessor $skuProcessor,
        \Magento\CatalogImportExport\Model\Import\Product\CategoryProcessor $categoryProcessor,
        \Magento\CatalogImportExport\Model\Import\Product\Validator $validator,
        ObjectRelationProcessor $objectRelationProcessor,
        TransactionManagerInterface $transactionManager,
        \Magento\CatalogImportExport\Model\Import\Product\TaxClassProcessor $taxClassProcessor,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\Product\Url $productUrl,
        array $data = [],
        array $dateAttrCodes = [],
        ?CatalogConfig $catalogConfig = null,
        ?\Magento\CatalogImportExport\Model\Import\Product\ImageTypeProcessor $imageTypeProcessor = null,
        ?\Magento\CatalogImportExport\Model\Import\Product\MediaGalleryProcessor $mediaProcessor = null,
        ?\Magento\CatalogImportExport\Model\StockItemImporterInterface $stockItemImporter = null,
        ?\Magento\Framework\Intl\DateTimeFactory $dateTimeFactory = null,
        ?\Magento\Catalog\Api\ProductRepositoryInterface $productRepository = null,
        ?\Magento\CatalogImportExport\Model\Import\Product\StatusProcessor $statusProcessor = null,
        ?\Magento\CatalogImportExport\Model\Import\Product\StockProcessor $stockProcessor = null,
        ?\Magento\CatalogImportExport\Model\Import\Product\LinkProcessor $linkProcessor = null,
        ?\Magento\Framework\Filesystem\Driver\File $fileDriver = null,
        ?\Magento\CatalogImportExport\Model\StockItemProcessorInterface $stockItemProcessor = null,
        ?SkuStorage $skuStorage = null
    ) {

        $this->catalogConfig = $catalogConfig ?: ObjectManager::getInstance()->get(CatalogConfig::class);
        $this->state = $state ?: ObjectManager::getInstance()->get(PluginState::class);
        $this->skuStorage = $skuStorage ?: ObjectManager::getInstance()->get(SkuStorage::class);
        parent::__construct(
            $jsonHelper,
            $importExportData,
            $importData,
            $config,
            $resource,
            $resourceHelper,
            $string,
            $errorAggregator,
            $eventManager,
            $stockRegistry,
            $stockConfiguration,
            $stockStateProvider,
            $catalogData,
            $importConfig,
            $resourceFactory,
            $optionFactory,
            $setColFactory,
            $productTypeFactory,
            $linkFactory,
            $proxyProdFactory,
            $uploaderFactory,
            $filesystem,
            $stockResItemFac,
            $localeDate,
            $dateTime,
            $logger,
            $indexerRegistry,
            $storeResolver,
            $skuProcessor,
            $categoryProcessor,
            $validator,
            $objectRelationProcessor,
            $transactionManager,
            $taxClassProcessor,
            $scopeConfig,
            $productUrl,
            $data,
            $dateAttrCodes,
            $catalogConfig,
            $imageTypeProcessor,
            $mediaProcessor,
            $stockItemImporter,
            $dateTimeFactory,
            $productRepository,
            $statusProcessor,
            $stockProcessor,
            $linkProcessor,
            $fileDriver,
            $stockItemProcessor,
            $skuStorage
        );
    }

    /**
     * Initialize product type models.
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _initTypeModels()
    {
        parent::_initTypeModels();
        if (!$this->state->isRunning()) {
            $this->_productTypeModels['giftvoucher'] = $this->_productTypeModels['simple'];
        }
        return $this;
    }

    /**
     * Validate data row.
     *
     * @param array $rowData
     * @param int $rowNum
     * @return boolean
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function validateRow(array $rowData, $rowNum)
    {
        if (!$this->state->isRunning()) {
            return parent::validateRow($rowData, $rowNum);
        }

        if (isset($this->_validatedRows[$rowNum])) {
            // check that row is already validated
            return !$this->getErrorAggregator()->isRowInvalid($rowNum);
        }
        $this->_validatedRows[$rowNum] = true;

        $rowScope = $this->getRowScope($rowData);

        // BEHAVIOR_DELETE and BEHAVIOR_REPLACE use specific validation logic
        if (Import::BEHAVIOR_REPLACE == $this->getBehavior()) {
            if (self::SCOPE_DEFAULT == $rowScope && !$this->isSkuExist($rowData[self::COL_SKU])) {
                $this->addRowError(ValidatorInterface::ERROR_SKU_NOT_FOUND_FOR_DELETE, $rowNum);
                return false;
            }
        }
        if (Import::BEHAVIOR_DELETE == $this->getBehavior()) {
            if (self::SCOPE_DEFAULT == $rowScope && !$this->isSkuExist($rowData[self::COL_SKU])) {
                $this->addRowError(ValidatorInterface::ERROR_SKU_NOT_FOUND_FOR_DELETE, $rowNum);
                return false;
            }
            return true;
        }

        $sku = $rowData[self::COL_SKU];
        $hasValidatedImportParent = $sku && $this->getNewSku($sku);
        $contextRowData = array_merge(['has_import_parent' => $hasValidatedImportParent], $rowData);
        if (!$this->validator->isValid($contextRowData)) {
            foreach ($this->validator->getMessages() as $message) {
                $this->addRowError($message, $rowNum, $this->validator->getInvalidAttribute());
            }
        }

        $sku = $rowData[self::COL_SKU];
        if (null === $sku) {
            $this->addRowError(ValidatorInterface::ERROR_SKU_IS_EMPTY, $rowNum);
        } elseif (false === $sku) {
            $this->addRowError(ValidatorInterface::ERROR_ROW_IS_ORPHAN, $rowNum);
        } elseif (self::SCOPE_STORE == $rowScope
            && !$this->storeResolver->getStoreCodeToId($rowData[self::COL_STORE])
        ) {
            $this->addRowError(ValidatorInterface::ERROR_INVALID_STORE, $rowNum);
        }

        // SKU is specified, row is SCOPE_DEFAULT, new product block begins
        $this->_processedEntitiesCount++;

        $sku = $rowData[self::COL_SKU];

        $isNewProduct = !$this->isSkuExist($sku) || (Import::BEHAVIOR_REPLACE == $this->getBehavior());
        if (!$isNewProduct) {
            // can we get all necessary data from existent DB product?
            // check for supported type of existing product
            if (isset($this->_productTypeModels[$this->getExistingSkuData($sku)['type_id']])) {
                $this->skuProcessor->addNewSku(
                    $sku,
                    $this->prepareNewSkuDataModified($sku, $rowData)
                );
            } else {
                $this->addRowError(ValidatorInterface::ERROR_TYPE_UNSUPPORTED, $rowNum);
                // child rows of legacy products with unsupported types are orphans
                $sku = false;
            }
        } else {
            // validate new product type and attribute set
            if (!isset($rowData[self::COL_TYPE]) || !isset($this->_productTypeModels[$rowData[self::COL_TYPE]])) {
                $this->addRowError(ValidatorInterface::ERROR_INVALID_TYPE, $rowNum);
            } elseif (!isset(
                    $rowData[self::COL_ATTR_SET]
                ) || !isset(
                    $this->_attrSetNameToId[$rowData[self::COL_ATTR_SET]]
                )
            ) {
                $this->addRowError(ValidatorInterface::ERROR_INVALID_ATTR_SET, $rowNum);
            } elseif ($this->skuProcessor->getNewSku($sku) === null) {
                $this->skuProcessor->addNewSku(
                    $sku,
                    [
                        'row_id' => null,
                        'entity_id' => null,
                        'type_id' => $rowData[self::COL_TYPE],
                        'attr_set_id' => $this->_attrSetNameToId[$rowData[self::COL_ATTR_SET]],
                        'attr_set_code' => $rowData[self::COL_ATTR_SET],
                    ]
                );
            }
            if ($this->getErrorAggregator()->isRowInvalid($rowNum)) {
                // mark SCOPE_DEFAULT row as invalid for future child rows if product not in DB already
                $sku = false;
            }
        }

        if (!$this->getErrorAggregator()->isRowInvalid($rowNum)) {
            $newSku = $this->skuProcessor->getNewSku($sku);
            // set attribute set code into row data for followed attribute validation in type model
            $rowData[self::COL_ATTR_SET] = $newSku['attr_set_code'];

            $rowAttributesValid = $this->_productTypeModels[$newSku['type_id']]->isRowValid(
                $rowData,
                $rowNum,
                $isNewProduct
            );
            if (!$rowAttributesValid && self::SCOPE_DEFAULT == $rowScope) {
                // mark SCOPE_DEFAULT row as invalid for future child rows if product not in DB already
                $sku = false;
            }
        }
        // validate custom options
        $this->getOptionEntity()->validateRow($rowData, $rowNum);

        if ($this->isNeedToValidateUrlKeyModified($rowData)) {
            $urlKey = $this->getUrlKey($rowData);
            $storeCodes = empty($rowData[self::COL_STORE_VIEW_CODE])
                ? array_flip($this->storeResolver->getStoreCodeToId())
                : explode($this->getMultipleValueSeparator(), $rowData[self::COL_STORE_VIEW_CODE]);
            foreach ($storeCodes as $storeCode) {
                $storeId = $this->storeResolver->getStoreCodeToId($storeCode);
                $productUrlSuffix = $this->getProductUrlSuffix($storeId);
                $urlPath = $urlKey . $productUrlSuffix;
                if (empty($this->urlKeys[$storeId][$urlPath])
                    || ($this->urlKeys[$storeId][$urlPath] == $rowData[self::COL_SKU])
                ) {
                    $this->urlKeys[$storeId][$urlPath] = $rowData[self::COL_SKU];
                    $this->rowNumbers[$storeId][$urlPath] = $rowNum;
                } else {
                    $this->addRowError(ValidatorInterface::ERROR_DUPLICATE_URL_KEY, $rowNum);
                }
            }
        }
        return !$this->getErrorAggregator()->isRowInvalid($rowNum);
    }

    /**
     * @param array $rowData
     * @return bool
     */
    private function isNeedToValidateUrlKeyModified($rowData)
    {
        return (!empty($rowData[self::URL_KEY]) || !empty($rowData[self::COL_NAME]))
            && (empty($rowData[self::COL_VISIBILITY])
                || $rowData[self::COL_VISIBILITY]
                !== (string)Visibility::getOptionArray()[Visibility::VISIBILITY_NOT_VISIBLE]);
    }

    /**
     * Prepare new SKU data
     *
     * @param string $sku
     * @return array
     */
    private function prepareNewSkuDataModified($sku, array $rowData)
    {
        $data = [];
        foreach ($this->getExistingSkuData($sku) as $key => $value) {
            $data[$key] = $value;
        }

        if ($rowData[self::COL_TYPE] === 'configurable') {
            $data['type_id'] = $rowData[self::COL_TYPE];
        }

        $data['attr_set_code'] = $this->_attrSetIdToName[$this->getExistingSkuData($sku)['attr_set_id']];

        return $data;
    }

    /**
     * Gather and save information about product entities.
     *
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _saveProducts()
    {
        if (!$this->state->isRunning()) {
            return parent::_saveProducts();
        }

        $priceIsGlobal = $this->_catalogData->isPriceGlobal();
        $entityLinkField = $this->getProductEntityLinkField();

        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $this->_eventManager->dispatch(
                'catalog_product_import_bunch_save_before',
                ['adapter' => $this, 'bunch' => $bunch]
            );

            $butchData = $this->buildButchData(
                $priceIsGlobal,
                $entityLinkField,
                $bunch
            );

            $this->saveProductEntity(
                $butchData['entityRowsIn'],
                $butchData['entityRowsUp']
            )->_saveProductWebsites(
                $butchData['websites']
            )->_saveProductCategories(
                $butchData['categories']
            )->deleteTierPrices(
                $butchData['tierPrices']
            )->_saveProductTierPrices(
                $butchData['tierPrices']
            )->_saveMediaGallery(
                empty($butchData['mediaGallery']) ? $butchData['mediaGallery'] : [$butchData['mediaGallery']]
            )->_saveProductAttributes(
                $butchData['attributes']
            );
            $this->websitesCache = array_merge($butchData['websites'], $this->websitesCache);
            $this->categoriesCache = array_merge($butchData['categories'], $this->categoriesCache);
            $this->_eventManager->dispatch(
                'catalog_product_import_bunch_save_after',
                ['adapter' => $this, 'bunch' => $bunch]
            );
        }
        return $this;
    }

    /**
     * method tries to remove tier prices for the product if import row do not have tier prices
     * @param $tierPriceData
     * @return mixed
     * @throws \Exception
     */
    private function deleteTierPrices(&$tierPriceData)
    {
        static $tableName = null;
        if (!$tableName) {
            $tableName = $this->_resourceFactory->create()->getTable('catalog_product_entity_tier_price');
        }

        if ($tierPriceData) {
            $removeTierPrices = [];
            foreach ($tierPriceData as $delSku => $tierPriceRows) {
                if (!empty($tierPriceRows['delete'])) {
                    $removeTierPrices[] = $this->skuProcessor->getNewSku($delSku)[$this->getProductEntityLinkField()];
                    unset($tierPriceData[$delSku]);
                }
            }
            if (!empty($removeTierPrices)) {
                $this->_connection->delete(
                    $tableName,
                    $this->_connection->quoteInto("{$this->getProductEntityLinkField()} IN (?)", $removeTierPrices)
                );
            }
        }
        return $this;
    }

    /**
     * Gather and save information about product links.
     * Must be called after ALL products saving done.
     *
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _saveLinks()
    {
        if (!$this->state->isRunning()) {
            return parent::_saveLinks();
        }

        $resource = $this->_linkFactory->create();
        $mainTable = $resource->getMainTable();
        $positionAttrId = [];
        $nextLinkId = $this->_resourceHelper->getNextAutoincrement($mainTable);

        // pre-load 'position' attributes ID for each link type once
        foreach ($this->_linkNameToId as $linkName => $linkId) {
            $select = $this->_connection->select()->from(
                $resource->getTable('catalog_product_link_attribute'),
                ['id' => 'product_link_attribute_id']
            )->where(
                'link_type_id = :link_id AND product_link_attribute_code = :position'
            );
            $bind = [':link_id' => $linkId, ':position' => 'position'];
            $positionAttrId[$linkId] = $this->_connection->fetchOne($select, $bind);
        }
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $productIds = [];
            $linkRows = [];
            $positionRows = [];

            foreach ($bunch as $rowNum => $rowData) {
                if (!$this->isRowAllowedToImport($rowData, $rowNum)) {
                    continue;
                }

                $sku = $rowData[self::COL_SKU];

                $productId = $this->skuProcessor->getNewSku($sku)[$this->getProductEntityLinkField()];
                $productLinkKeys = [];
                $select = $this->_connection->select()->from(
                    $resource->getTable('catalog_product_link'),
                    ['id' => 'link_id', 'linked_id' => 'linked_product_id', 'link_type_id' => 'link_type_id']
                )->where(
                    'product_id = :product_id'
                );
                $bind = [':product_id' => $productId];
                foreach ($this->_connection->fetchAll($select, $bind) as $linkData) {
                    $linkKey = "{$productId}-{$linkData['linked_id']}-{$linkData['link_type_id']}";
                    $productLinkKeys[$linkKey] = $linkData['id'];
                }
                foreach ($this->_linkNameToId as $linkName => $linkId) {
                    $productIds[] = $productId;
                    if (isset($rowData[$linkName . 'sku'])) {
                        $linkSkus = explode($this->getMultipleValueSeparator(), $rowData[$linkName . 'sku']);
                        $linkPositions = !empty($rowData[$linkName . 'position'])
                            ? explode($this->getMultipleValueSeparator(), $rowData[$linkName . 'position'])
                            : [];
                        foreach ($linkSkus as $linkedKey => $linkedSku) {
                            $linkedSku = trim((string)$linkedSku);
                            if ((!is_null(
                                        $this->skuProcessor->getNewSku($linkedSku)
                                    ) || $this->isSkuExist($linkedSku)
                                    ) && $linkedSku != $sku
                            ) {
                                $newSku = $this->skuProcessor->getNewSku($linkedSku);
                                if (!empty($newSku)) {
                                    $linkedId = $newSku['entity_id'];
                                } else {
                                    $linkedId = $this->getExistingSkuData($linkedSku)['entity_id'];
                                }

                                if ($linkedId == null) {
                                    // Import file links to a SKU which is skipped for some reason,
                                    // which leads to a "NULL"
                                    // link causing fatal errors.
                                    continue;
                                }

                                $linkKey = "{$productId}-{$linkedId}-{$linkId}";
                                if (empty($productLinkKeys[$linkKey])) {
                                    $productLinkKeys[$linkKey] = $nextLinkId;
                                }
                                if (!isset($linkRows[$linkKey])) {
                                    $linkRows[$linkKey] = [
                                        'link_id' => $productLinkKeys[$linkKey],
                                        'product_id' => $productId,
                                        'linked_product_id' => $linkedId,
                                        'link_type_id' => $linkId,
                                    ];
                                    if (!empty($linkPositions[$linkedKey])) {
                                        $positionRows[] = [
                                            'link_id' => $productLinkKeys[$linkKey],
                                            'product_link_attribute_id' => $positionAttrId[$linkId],
                                            'value' => $linkPositions[$linkedKey],
                                        ];
                                    }
                                    $nextLinkId++;
                                }
                            }
                        }
                    }
                }
            }

            if ($linkRows) {
                $this->_connection->delete(
                    $mainTable,
                    $this->_connection->quoteInto('product_id IN (?)', array_unique($productIds))
                );

                $this->_connection->insertOnDuplicate($mainTable, $linkRows, ['link_id']);
            }

            if ($positionRows) {
                // process linked product positions
                $this->_connection->insertOnDuplicate(
                    $resource->getAttributeTypeTable('int'),
                    $positionRows,
                    ['value']
                );
            }
        }
        return $this;
    }

    /**
     * Save product categories.
     *
     * @param array $categoriesData
     * @return $this
     */
    protected function _saveProductCategories(array $categoriesData)
    {
        static $tableName = null;

        if (!$this->state->isRunning()) {
            return parent::_saveProductCategories($categoriesData);
        }

        if (!$tableName) {
            $tableName = $this->_resourceFactory->create()->getProductCategoryTable();
        }
        if ($categoriesData) {
            $categoriesIn = [];
            $delProductId = [];

            foreach ($categoriesData as $delSku => $categories) {
                $productId = $this->skuProcessor->getNewSku($delSku)['entity_id'];
                $delProductId[] = $productId;

                // Fetch old position of products in category
                $select = $this->_connection->select()
                    ->from($tableName, ['category_id', 'position'])
                    ->where('product_id=?', $productId);
                $oldCategoryIds = $this->_connection->fetchAll($select);
                if (is_array($oldCategoryIds) && !empty($oldCategoryIds)) {
                    foreach ($oldCategoryIds as $row) {
                        $oldCategoryIds[$row['category_id']] = $row['position'];
                    }
                } else {
                    $oldCategoryIds = [];
                }

                foreach (array_keys($categories) as $categoryId) {
                    $newPosition = self::EMPTY_POSITION;
                    if (isset($oldCategoryIds[$categoryId])) {
                        $newPosition = $oldCategoryIds[$categoryId];
                    }
                    $categoriesIn[] = [
                        'product_id' => $productId,
                        'category_id' => $categoryId,
                        'position' => $newPosition
                    ];
                }
            }

            $this->_connection->delete(
                $tableName,
                $this->_connection->quoteInto('product_id IN (?)', $delProductId)
            );

            if ($categoriesIn) {
                $this->_connection->insertOnDuplicate($tableName, $categoriesIn, ['product_id', 'category_id']);
            }
        }
        return $this;
    }

    /**
     * Save product websites.
     *
     * @param array $websiteData
     * @return mixed
     */
    protected function _saveProductWebsites(array $websiteData)
    {
        static $tableName = null;

        if (!$this->state->isRunning()) {
            return parent::_saveProductWebsites($websiteData);
        }

        if (!$tableName) {
            $tableName = $this->_resourceFactory->create()->getProductWebsiteTable();
        }
        if ($websiteData) {
            $websitesData = [];
            $delProductId = [];

            foreach ($websiteData as $delSku => $websites) {
                $productId = $this->skuProcessor->getNewSku($delSku)['entity_id'];
                $delProductId[] = $productId;

                foreach (array_keys($websites) as $websiteId) {
                    $websitesData[] = ['product_id' => $productId, 'website_id' => $websiteId];
                }
            }

            $this->_connection->delete(
                $tableName,
                $this->_connection->quoteInto('product_id IN (?)', $delProductId)
            );

            if ($websitesData) {
                $this->_connection->insertOnDuplicate($tableName, $websitesData);
            }
        }
        return $this;
    }

    /**
     * Get product entity link field
     * Note: functionality is the same as in parent class, we just can't call private method
     *
     * @return string
     * @throws \Exception
     */
    private function getProductEntityLinkField()
    {
        static $productEntityLinkField;

        if (!$productEntityLinkField) {
            $productEntityLinkField = $this->getMetadataPool()
                ->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class)
                ->getLinkField();
        }

        return $productEntityLinkField;
    }

    public function getProductCategories($productSku)
    {
        if (isset($this->categoriesCache[$productSku])) {
            return parent::getProductCategories($productSku);
        }
        return [];
    }

    public function getProductWebsites($productSku)
    {
        if (isset($this->websitesCache[$productSku])) {
            return parent::getProductWebsites($productSku);
        }
        return [];
    }

    /**
     * Gather and save information about product entities.
     *
     * @param $priceIsGlobal
     * @param $entityLinkField
     * @param $bunch
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function buildButchData($priceIsGlobal, $entityLinkField, $bunch)
    {
        $result = [
            'entityRowsIn' => [],
            'entityRowsUp' => [],
            'websites' => [],
            'categories' => [],
            'tierPrices' => [],
            'mediaGallery' => [],
            'attributes' => []
        ];

        $uploadedImages = [];
        $previousType = null;
        $prevAttributeSet = null;
        $existingImages = $this->getExistingImages($bunch);

        foreach ($bunch as $rowNum => $rowData) {
            // reset category processor's failed categories array
            $this->categoryProcessor->clearFailedCategories();

            if (!$this->validateRow($rowData, $rowNum)) {
                continue;
            }
            if ($this->getErrorAggregator()->hasToBeTerminated()) {
                $this->getErrorAggregator()->addRowToSkip($rowNum);
                continue;
            }
            $rowScope = $this->getRowScope($rowData);

            $rowData[self::URL_KEY] = $this->getUrlKey($rowData);

            $defaultPageLayout = $this->scopeConfig->getValue(self::PAGE_LAYOUT_DEFAULT_PATH);
            if (!empty($defaultPageLayout)) {
                $rowData[self::PAGE_LAYOUT_ATTR] = $this->catalogConfig->getAttribute(
                    ProductAttributeInterface::ENTITY_TYPE_CODE,
                    self::PAGE_LAYOUT_ATTR
                )
                    ->getSource()
                    ->getOptionText($defaultPageLayout);
            }

            $rowSku = $rowData[self::COL_SKU];

            if (null === $rowSku) {
                $this->getErrorAggregator()->addRowToSkip($rowNum);
                continue;
            } elseif (self::SCOPE_STORE == $rowScope) {
                // set necessary data from SCOPE_DEFAULT row
                $rowData[self::COL_TYPE] = $this->skuProcessor->getNewSku($rowSku)['type_id'];
                $rowData['attribute_set_id'] = $this->skuProcessor->getNewSku($rowSku)['attr_set_id'];
                $rowData[self::COL_ATTR_SET] = $this->skuProcessor->getNewSku($rowSku)['attr_set_code'];
            }
            $lowercaseSku = strtolower($rowSku);

            // 1. Entity phase
            $existingSkuData = $this->getExistingSkuData($rowSku);
            if ($existingSkuData !== null) {
                // existing row
                if (isset($rowData['attribute_set_code'])) {
                    $attributeSetId = $this->catalogConfig->getAttributeSetId(
                        $this->getEntityTypeId(),
                        $rowData['attribute_set_code']
                    );

                    // wrong attribute_set_code was received
                    if (!$attributeSetId) {
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __(
                                'Wrong attribute set code "%1", please correct it and try again.',
                                $rowData['attribute_set_code']
                            )
                        );
                    }
                } else {
                    $attributeSetId = $this->skuProcessor->getNewSku($rowSku)['attr_set_id'];
                }

                $result['entityRowsUp'][] = [
                    'attribute_set_id' => $attributeSetId,
                    $entityLinkField => $existingSkuData[$this->getProductEntityLinkField()],
                ];
            } else {
                $result['entityRowsIn'][$rowSku] = [
                    'attribute_set_id' => $this->skuProcessor->getNewSku($rowSku)['attr_set_id'],
                    'type_id' => $this->skuProcessor->getNewSku($rowSku)['type_id'],
                    'sku' => $rowSku,
                    'has_options' => isset($rowData['has_options']) ? $rowData['has_options'] : 0,
                    'created_at' => (new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT),
                ];
            }

            if (!array_key_exists($rowSku, $this->websitesCache)) {
                $this->websitesCache[$rowSku] = [];
            }
            // 2. Product-to-Website phase
            if (!empty($rowData[self::COL_PRODUCT_WEBSITES])) {
                $websiteCodes = explode($this->getMultipleValueSeparator(), $rowData[self::COL_PRODUCT_WEBSITES]);
                foreach ($websiteCodes as $websiteCode) {
                    $websiteId = $this->storeResolver->getWebsiteCodeToId($websiteCode);
                    $this->websitesCache[$rowSku][$websiteId] = true;
                }
            }

            // 3. Categories phase
            if (isset($rowData['categories'])) {
                if (!array_key_exists($rowSku, $this->categoriesCache)) {
                    $this->categoriesCache[$rowSku] = [];
                }
                $rowData['rowNum'] = $rowNum;
                $categoryIds = $this->processRowCategories($rowData);
                foreach ($categoryIds as $id) {
                    $this->categoriesCache[$rowSku][$id] = true;
                }
                unset($rowData['rowNum']);
            }

            // 4.1. Tier prices phase
            if (!empty($rowData['_tier_price_website'])) {
                $result ['tierPrices'][$rowSku][] = [
                    'all_groups' => $rowData['_tier_price_customer_group'] == self::VALUE_ALL,
                    'customer_group_id' => $rowData['_tier_price_customer_group'] ==
                    self::VALUE_ALL ? 0 : $rowData['_tier_price_customer_group'],
                    'qty' => $rowData['_tier_price_qty'],
                    'value' => $rowData['_tier_price_price'],
                    'website_id' => self::VALUE_ALL == $rowData['_tier_price_website'] ||
                    $priceIsGlobal ? 0 : $this->storeResolver->getWebsiteCodeToId($rowData['_tier_price_website']),
                ];
            }
            if (empty($rowData['tier_prices'])) {
                $result ['tierPrices'][$rowSku]['delete'] = true;
            }

            if (!$this->validateRow($rowData, $rowNum)) {
                continue;
            }

            // 5. Media gallery phase
            $disabledImages = [];
            list($rowImages, $rowLabels) = $this->getImagesFromRow($rowData);
            if (isset($rowData['_media_is_disabled'])) {
                $disabledImages = array_flip(
                    explode($this->getMultipleValueSeparator(), $rowData['_media_is_disabled'])
                );
            }
            $rowData[self::COL_MEDIA_IMAGE] = [];
            foreach ($rowImages as $column => $columnImages) {
                foreach ($columnImages as $position => $columnImage) {
                    if (!isset($uploadedImages[$columnImage])) {
                        $uploadedFile = $this->uploadMediaFiles(trim((string)$columnImage), true);
                        if ($uploadedFile) {
                            $uploadedImages[$columnImage] = $uploadedFile;
                        } else {
                            $this->addRowError(
                                ValidatorInterface::ERROR_MEDIA_URL_NOT_ACCESSIBLE,
                                $rowNum,
                                null,
                                null,
                                ProcessingError::ERROR_LEVEL_NOT_CRITICAL
                            );
                        }
                    } else {
                        $uploadedFile = $uploadedImages[$columnImage];
                    }

                    if ($uploadedFile && $column !== self::COL_MEDIA_IMAGE) {
                        $rowData[$column] = $uploadedFile;
                    }

                    $imageNotAssigned = !isset($existingImages[$rowSku][$uploadedFile]);

                    if ($uploadedFile && $imageNotAssigned) {
                        if ($column == self::COL_MEDIA_IMAGE) {
                            $rowData[$column][] = $uploadedFile;
                        }
                        $result['mediaGallery'][$rowSku][] = [
                            'attribute_id' => $this->getMediaGalleryAttributeId(),
                            'label' => isset($rowLabels[$column][$position]) ? $rowLabels[$column][$position] : '',
                            'position' => $position + 1,
                            'disabled' => isset($disabledImages[$columnImage]) ? '1' : '0',
                            'value' => $uploadedFile,
                        ];
                        $existingImages[$rowSku][$uploadedFile] = true;
                    }
                }
            }

            // 6. Attributes phase
            $rowStore = (self::SCOPE_STORE == $rowScope)
                ? $this->storeResolver->getStoreCodeToId($rowData[self::COL_STORE])
                : 0;
            $productType = isset($rowData[self::COL_TYPE]) ? $rowData[self::COL_TYPE] : null;
            if (!is_null($productType)) {
                $previousType = $productType;
            }
            if (isset($rowData[self::COL_ATTR_SET])) {
                $prevAttributeSet = $rowData[self::COL_ATTR_SET];
            }
            if (self::SCOPE_NULL == $rowScope) {
                // for multiselect attributes only
                if (!is_null($prevAttributeSet)) {
                    $rowData[self::COL_ATTR_SET] = $prevAttributeSet;
                }
                if (is_null($productType) && !is_null($previousType)) {
                    $productType = $previousType;
                }
                if (is_null($productType)) {
                    continue;
                }
            }

            $productTypeModel = $this->_productTypeModels[$productType];
            if (!empty($rowData['tax_class_name'])) {
                $rowData['tax_class_id'] =
                    $this->taxClassProcessor->upsertTaxClass($rowData['tax_class_name'], $productTypeModel);
            }

            if ($this->getBehavior() == Import::BEHAVIOR_APPEND ||
                empty($rowData[self::COL_SKU])
            ) {
                $rowData = $productTypeModel->clearEmptyData($rowData);
            }

            $rowData = $productTypeModel->prepareAttributesWithDefaultValueForSave(
                $rowData,
                !$this->isSkuExist($rowSku)
            );
            $product = $this->_proxyProdFactory->create(['data' => $rowData]);

            foreach ($rowData as $attrCode => $attrValue) {
                $attribute = $this->retrieveAttributeByCode($attrCode);

                if ('multiselect' != $attribute->getFrontendInput() && self::SCOPE_NULL == $rowScope) {
                    // skip attribute processing for SCOPE_NULL rows
                    continue;
                }
                $attrId = $attribute->getId();
                $backModel = $attribute->getBackendModel();
                $attrTable = $attribute->getBackend()->getTable();
                $storeIds = [0];

                if ('datetime' == $attribute->getBackendType()
                    && (
                        in_array($attribute->getAttributeCode(), $this->dateAttrCodes)
                        || $attribute->getIsUserDefined()
                    )
                ) {
                    $attrValue = $this->dateTime->formatDate($attrValue, false);
                } elseif ('datetime' == $attribute->getBackendType() && strtotime($attrValue)) {
                    $attrValue = $this->dateTime->gmDate(
                        'Y-m-d H:i:s',
                        $this->_localeDate->date($attrValue)->getTimestamp()
                    );
                } elseif ($backModel) {
                    $attribute->getBackend()->beforeSave($product);
                    $attrValue = $product->getData($attribute->getAttributeCode());
                }
                if (self::SCOPE_STORE == $rowScope) {
                    if (self::SCOPE_WEBSITE == $attribute->getIsGlobal()) {
                        // check website defaults already set
                        if (!isset($result['attributes'][$attrTable][$rowSku][$attrId][$rowStore])) {
                            $storeIds = $this->storeResolver->getStoreIdToWebsiteStoreIds($rowStore);
                        }
                    } elseif (self::SCOPE_STORE == $attribute->getIsGlobal()) {
                        $storeIds = [$rowStore];
                    }
                    if (!$this->isSkuExist($rowSku)) {
                        $storeIds[] = 0;
                    }
                }
                foreach ($storeIds as $storeId) {
                    if (!isset($result['attributes'][$attrTable][$rowSku][$attrId][$storeId])) {
                        $result['attributes'][$attrTable][$rowSku][$attrId][$storeId] = $attrValue;
                    }
                }
                // restore 'backend_model' to avoid 'default' setting
                $attribute->setBackendModel($backModel);
            }
        }

        foreach ($bunch as $rowNum => $rowData) {
            if ($this->getErrorAggregator()->isRowInvalid($rowNum)) {
                unset($bunch[$rowNum]);
            }
        }

        $result['websites'] = $this->websitesCache;
        $result['categories'] = $this->categoriesCache;

        return $result;
    }

    /**
     * Check if SKU exists in storage
     *
     * @param string|null $sku
     * @return bool
     */
    private function isSkuExist($sku): bool
    {
        return $sku !== null && $this->skuStorage->has(strtolower($sku));
    }

    /**
     * Get existing SKU data from storage
     *
     * @param string $sku
     * @return array|null
     */
    private function getExistingSkuData(string $sku): ?array
    {
        return $this->skuStorage->get(strtolower($sku));
    }
}
