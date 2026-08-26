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
 */

namespace MageOS\NetSuiteConnector\Product\Model\Mapper;

use Magento\Framework\DataObject;
use Magento\Framework\Stdlib\DateTime;
use NetSuite\Classes\Record;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\ConvertDate;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\NetsuiteCountries;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\ServiceRepository;
use MageOS\NetSuiteConnector\Product\Model\Config\ProductConfig;
use MageOS\NetSuiteConnector\Core\Model\ImportQueueManager;
use MageOS\NetSuiteConnector\Core\Model\ImportRowList;
use MageOS\NetSuiteConnector\Core\Model\MagentoTables;
use MageOS\NetSuiteConnector\Product\Model\Prefetch\ProductPrefetchIdSource;

/**
 * Abstract class for Product mapping. It takes care of majority of logic so only
 * productType-specific are handled in the actual classes.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class Product
{
    /**
     * @var int
     */
    public const CUST_ALL_GROUPS_ID = 32000;

    /**
     * @var string
     */
    public const CUST_ALL_GROUPS_NAME = 'ALL GROUPS';

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $dbConnection;
    /**
     * @var ImportQueueManager
     */
    protected $importManager;
    /**
     * @var \MageOS\NetSuiteConnector\Core\Helper\EavHelper
     */
    protected $eavHelper;
    /**
     * @var ProductConfig
     */
    protected $importConfig;
    /**
     * @var ProductPrefetchIdSource
     */
    protected $productPrefetch;
    /**
     * @var \MageOS\NetSuiteConnector\Product\Model\Product\Map\ValueFactory
     */
    protected $valueFactory;
    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\ImportRowListFactory
     */
    protected $importRowListFactory;
    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\ImportRowList
     */
    protected $importResult;
    /**
     * @var BaseData
     */
    protected $productBaseData;
    /**
     * @var \MageOS\NetSuiteConnector\Product\Model\Prefetch\ProcessingItem
     */
    protected $prefetchProcessingItem;

    /**
     * @var ServiceRepository
     */
    protected $serviceRepository;

    /**
     * Product constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\App\ResourceConnection $dbConnection
     * @param \MageOS\NetSuiteConnector\Product\Model\Product\Map\ValueFactory $valueFactory
     * @param \MageOS\NetSuiteConnector\Core\Model\ImportRowListFactory $importRowListFactory
     * @param ImportQueueManager $importManager
     * @param \MageOS\NetSuiteConnector\Core\Helper\EavHelper $eavHelper
     * @param ProductConfig $importConfig
     * @param ProductPrefetchIdSource $productPrefetch
     * @param BaseData $productBaseData
     * @param \MageOS\NetSuiteConnector\Product\Model\Prefetch\ProcessingItem $prefetchProcessingItem
     * @param \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Repository $serviceRepository
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\App\ResourceConnection $dbConnection,
        \MageOS\NetSuiteConnector\Product\Model\Product\Map\ValueFactory $valueFactory,
        \MageOS\NetSuiteConnector\Core\Model\ImportRowListFactory $importRowListFactory,
        \MageOS\NetSuiteConnector\Core\Model\ImportQueueManager $importManager,
        \MageOS\NetSuiteConnector\Core\Helper\EavHelper $eavHelper,
        \MageOS\NetSuiteConnector\Product\Model\Config\ProductConfig $importConfig,
        \MageOS\NetSuiteConnector\Product\Model\Prefetch\ProductPrefetchIdSource $productPrefetch,
        \MageOS\NetSuiteConnector\Product\Model\Mapper\BaseData $productBaseData,
        \MageOS\NetSuiteConnector\Product\Model\Prefetch\ProcessingItem $prefetchProcessingItem,
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Repository $serviceRepository
    ) {
        $this->eventManager = $context->getEventDispatcher();
        $this->dbConnection = $dbConnection;
        $this->importManager = $importManager;
        $this->eavHelper = $eavHelper;
        $this->importConfig = $importConfig;
        $this->productPrefetch = $productPrefetch;
        $this->valueFactory = $valueFactory;
        $this->importRowListFactory = $importRowListFactory;
        $this->productBaseData = $productBaseData;
        $this->prefetchProcessingItem = $prefetchProcessingItem;
        $this->serviceRepository = $serviceRepository;
    }

    /**
     * @param $magentoProduct
     * @param $inventoryItem
     * @return array
     */
    abstract public function getPricing($magentoProduct, $inventoryItem): array;

    /**
     * @param $inventoryItem
     * @return string
     */
    abstract public function getProductType($inventoryItem): string;

    /**
     * @param $magentoProduct
     * @param $inventoryItem
     * @return DataObject
     */
    abstract public function setProductType($magentoProduct, $inventoryItem): DataObject;

    /**
     * @TODO check old/new switch removing - retest regression with huge queue and manually created multiple entries
     *
     * @param $inventoryItem \NetSuite\Classes\InventoryItem|\NetSuite\Classes\KitItem|\NetSuite\Classes\Record
     * @param bool $isImportable
     * @return ImportRowList
     */
    public function mapInventoryItemToRowList($inventoryItem, $isImportable = true)
    {
        $oldImportResult = $this->importResult;
        $this->importResult = $this->importRowListFactory->create();

        $magentoProduct = $this->productBaseData->createProduct($inventoryItem);
        $productType = $this->getProductType($inventoryItem);
        $magentoProduct->setProductType($productType);

        // register product in queue so it won't be fetched again
        $this->importManager->pushRowToEntity('catalog_product', [
            'sku' => $magentoProduct->getSku(),
            '_incomplete' => true,
            'product_online' => $magentoProduct->getProductOnline(),
            'netsuite_internal_id' => $inventoryItem->internalId,
        ]);

        if ($magentoProduct->getSkipNetsuiteProcessing()) {
            return null;
        }

        $magentoProduct = $this->setProductData($magentoProduct, $inventoryItem);
        $magentoProduct = $this->setProductType($magentoProduct, $inventoryItem);

        $pricing = $this->getPricing($magentoProduct, $inventoryItem);
        $magentoProduct->setTierPrices($pricing);

        $magentoProduct = $this->setCustomAttributeValuesBeforeMapping($magentoProduct, $inventoryItem);

        if (!$isImportable) {
            $magentoProduct->setProductOnline(2);
        }

        $this->eventManager->dispatch(
            'netsuite_import_product_format_from_inventory_item_before',
            [
                'magento_product' => $magentoProduct,
                'netsuite_product' => $inventoryItem,
                'import_rows' => $this->importResult,
            ]
        );
        $this->productPrefetch->mapToProduct(
            $inventoryItem->internalId,
            $magentoProduct
        );

        $this->eventManager->dispatch('netsuite_import_product_format_from_inventory_item_after', [
            'magento_product' => $magentoProduct,
            'netsuite_product' => $inventoryItem,
            'import_rows' => $this->importResult,
        ]);

        $this->productBaseData->stripTagsForAddAttr($magentoProduct);
        $magentoProduct->setData('updated_at', (new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT));
        $this->addProductToResult($magentoProduct);
        $this->importResult->pushRowsToEntity('advanced_pricing', $pricing);

        // This switch doesn't make any sense to me.Why do we need to keep the $oldImportResult?!?!?
        $newImportResult = $this->importResult;
        $this->importResult = $oldImportResult;

        return $newImportResult;
    }

    public function isMagentoImportable(Record $inventoryItem)
    {
        $isImportable = new \stdClass();
        $isImportable->flag = true;
        $this->eventManager->dispatch(
            'netsuite_inventory_item_is_importable',
            [
                'inventory_item' => $inventoryItem,
                'is_importable' => $isImportable
            ]
        );

        return $isImportable->flag;
    }

    /**
     * @param $magentoProduct
     * @param $inventoryItem
     * @return DataObject
     */
    protected function setCustomAttributeValuesBeforeMapping(DataObject $magentoProduct, $inventoryItem)
    {
        $magentoProduct->setNetsuiteInternalId($inventoryItem->internalId);
        $magentoProduct->setNetsuiteLastImportDate(
            ConvertDate::fromNetSuiteToSql($inventoryItem->lastModifiedDate)
        );

        if (!empty($inventoryItem->countryOfManufacture)) {
            $magentoProduct->setCountryOfManufacture(
                NetsuiteCountries::netsuiteCountryCodeToRegularCountryCode($inventoryItem->countryOfManufacture)
            );
        }

        return $magentoProduct;
    }

    public function addProductToResult($magentoProduct)
    {
        // for each store view there should be a separate row (only for simple products?)
        $storeViewList = $magentoProduct->getStoreViewCode();
        $magentoProduct->setStoreViewCode('');

        if (!empty($storeViewList) && $magentoProduct->getProductType() === 'simple') {
            $storeViews = explode(',', $storeViewList);

            $productData = $magentoProduct->getData();

            $this->importResult->pushRowToEntity('catalog_product', $productData);

            foreach ($storeViews as $storeView) {
                $storeViewRow = [
                    'sku' => $productData['sku'],
                    'store_view_code' => $storeView,
                    'attribute_set_code' => $productData['attribute_set_code'],
                    'product_type' => $productData['product_type'],
                ];

                $this->importResult->pushRowToEntity('catalog_product', $storeViewRow);
            }
        } else {
            $this->importResult->pushRowToEntity('catalog_product', $magentoProduct->getData());
        }
    }

    /**
     * This method is used for Plugin needs
     *
     * @param DataObject $magentoProduct
     * @param \NetSuite\Classes\InventoryItem|\NetSuite\Classes\KitItem $inventoryItem
     *
     * @return DataObject
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setProductData($magentoProduct, $inventoryItem)
    {
        return $magentoProduct;
    }

    protected function clearProductTiers($productId)
    {
        if (!$productId) {
            return;
        }
        $table = $this->dbConnection->getTableName(MagentoTables::PRODUCT_TIER_PRICE);
        // phpcs:ignore
        $this->dbConnection->getConnection()->query("DELETE FROM $table WHERE entity_id = {$productId}");
    }
}
