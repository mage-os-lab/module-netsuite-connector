<?php

namespace MageOS\NetSuiteConnector\Core\Helper;

use Magento\CatalogImportExport\Model\Import\ProductFactory;
use Magento\CatalogImportExport\Model\Import\Product\Type\AbstractType;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Api\StoreRepositoryInterface;
use MageOS\NetSuiteConnector\Core\Model\MagentoTables;

class EavHelper
{
    private $productModels = null;
    /**
     * @var ResourceConnection
     */
    private $resource;
    /**
     * @var ProductFactory
     */
    private $productImportFactory;
    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * EavHelper constructor.
     * @param ResourceConnection $resource
     * @param StoreRepositoryInterface $storeRepository
     * @param ProductFactory $productImportFactory
     */
    public function __construct(
        ResourceConnection $resource,
        StoreRepositoryInterface $storeRepository,
        ProductFactory $productImportFactory
    ) {
        $this->resource = $resource;
        $this->productImportFactory = $productImportFactory;
        $this->storeRepository = $storeRepository;
    }

    /**
     * Add Attribute Option and *update import/export cache*
     *
     * @param $attribute
     * @param array $option
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function addAttributeOption($attribute, $option)
    {
        $connection = $this->resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $optionTable = $connection->getTableName(MagentoTables::EAV_ATTRIBUTE_OPTION);
        $optionValueTable = $connection->getTableName(MagentoTables::EAV_ATTRIBUTE_OPTION_VALUE);

        $isSwatch = $this->isTextSwatchAttribute($attribute);

        if (isset($option['value'])) {
            foreach ($option['value'] as $optionId => $values) {
                $intOptionId = (int)$optionId;
                if (!empty($option['delete'][$optionId])) {
                    if ($intOptionId) {
                        $condition = ['option_id =?' => $intOptionId];
                        $connection->delete($optionTable, $condition);
                    }
                    continue;
                }

                if (!$intOptionId) {
                    $data = [
                        'attribute_id' => $option['attribute_id'],
                        'sort_order' => isset($option['order'][$optionId]) ? $option['order'][$optionId] : 0,
                    ];
                    $connection->insert($optionTable, $data);
                    $intOptionId = $connection->lastInsertId($optionTable);
                } else {
                    $data = [
                        'sort_order' => isset($option['order'][$optionId]) ? $option['order'][$optionId] : 0,
                    ];
                    $connection->update($optionTable, $data, ['option_id=?' => $intOptionId]);
                }

                // Default value
                if (!isset($values[0])) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Default option value is not defined')
                    );
                }
                $condition = ['option_id =?' => $intOptionId];
                $connection->delete($optionValueTable, $condition);
                foreach ($values as $storeId => $value) {
                    $data = ['option_id' => $intOptionId, 'store_id' => $storeId, 'value' => $value];
                    $connection->insert($optionValueTable, $data);
                    $this->registerOptionValue(
                        $option['attribute_id'],
                        $option['attribute_code'],
                        $intOptionId,
                        $value
                    );
                }

                if ($isSwatch) {
                    $this->createSwatch($intOptionId, 1, $values[0]);
                }
            }
        } elseif (isset($option['values'])) {
            foreach ($option['values'] as $sortOrder => $label) {
                // add option
                $data = ['attribute_id' => $option['attribute_id'], 'sort_order' => $sortOrder];
                $connection->insert($optionTable, $data);
                $intOptionId = $connection->lastInsertId($optionTable);

                $data = ['option_id' => $intOptionId, 'store_id' => 0, 'value' => $label];
                $connection->insert($optionValueTable, $data);
                $this->registerOptionValue($option['attribute_id'], $option['attribute_code'], $intOptionId, $label);

                if ($isSwatch) {
                    $this->createSwatch($intOptionId, 1, $label);
                }
            }
        }
    }

    protected function createSwatch($optionId, $storeId, $value)
    {
        $connection = $this->resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $swatchTable = $connection->getTableName(MagentoTables::EAV_ATTRIBUTE_OPTION_SWATCH);

        // there should be no swatches for store_id == 0
        $connection->insert(
            $swatchTable,
            [
                'option_id' => $optionId,
                'store_id' => $storeId ?? 1,
                'value' => $value
            ]
        );
    }

    /**
     * @param \Magento\Eav\Model\Entity\Attribute $attribute
     * @return bool
     */
    protected function isTextSwatchAttribute(\Magento\Eav\Model\Entity\Attribute $attribute)
    {
        $additionalData = $attribute->getAdditionalData();
        if ($additionalData) {
            $additionalData = json_decode($additionalData, true);
            if (isset($additionalData[\Magento\Swatches\Model\Swatch::SWATCH_INPUT_TYPE_KEY]) &&
                $additionalData[\Magento\Swatches\Model\Swatch::SWATCH_INPUT_TYPE_KEY]
                === \Magento\Swatches\Model\Swatch::SWATCH_INPUT_TYPE_TEXT
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array
     */
    private function getProductModels()
    {
        if ($this->productModels) {
            return $this->productModels;
        }

        $productImport = $this->productImportFactory->create();

        $types = ['simple', 'configurable', 'bundle'];
        foreach ($types as $type) {
            $this->productModels[$type] = $productImport->retrieveProductTypeByName($type);
        }

        return $this->productModels;
    }

    private function registerOptionValue($attributeId, $attributeCode, $optionId, $optionLabel)
    {
        $productModels = $this->getProductModels();
        foreach ($productModels as $model) {
            $model->addAttributeOption($attributeCode, $optionLabel, $optionId);
        }

        $importAttrCache = \Magento\CatalogImportExport\Model\Import\Product\Type\AbstractType::$commonAttributesCache;
        if (isset($importAttrCache[$attributeId])) {
            $options = $importAttrCache[$attributeId]['options'] ?? [];

            $optionLabel = strtolower($optionLabel);
            $options[$optionLabel] = $optionId;
            AbstractType::$commonAttributesCache[$attributeId]['options'] = $options;

        }
    }

    /**
     * Returns ids of all existing stores
     * @return int[]
     */
    public function getAllStoreIds()
    {
        static $storeIds = null;
        if ($storeIds) {
            return $storeIds;
        }

        $storeIds = [];
        $stores = $this->storeRepository->getList();

        foreach ($stores as $store) {
            if ($store->getId() !== -1) {
                $storeIds[] = $store->getId();
            }
        }

        return $storeIds;
    }

    /**
     * Returns option_sku to netsuite_internal_id mapping for product bundles
     * @param int $productId
     * @return array
     */
    public function getBundleLinksForNetSuite(int $productId)
    {
        $connection = $this->resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
        $select = $connection->select()->from(
            ['l' => $connection->getTableName('mageos_netsuite_bundle_product_link')],
            ['option_sku', 'netsuite_internal_id']
        )->where('product_id=?', $productId);

        $rows = [];
        $stmt = $connection->query($select);
        while ($row = $stmt->fetch()) {
            $rows[$row['option_sku']] = $row['netsuite_internal_id'];
        }

        return $rows;
    }

    /**
     * Returns product ID given the netsuite id of the bundle option product
     * @param int $productId
     * @return int
     */
    public function getBundleProductIdByNetSuiteId(int $netsuiteId)
    {
        $connection = $this->resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
        $result = $connection->fetchPairs($connection->select()->from(
            ['l' => $connection->getTableName('mageos_netsuite_bundle_product_link')],
            ['netsuite_internal_id', 'product_id']
        )->where('netsuite_internal_id=?', $netsuiteId));

        return $result[$netsuiteId] ?? null;
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $magentoOrderItem
     * @return string|null
     */
    public function mapBundleOptionToInternalId(\Magento\Sales\Model\Order\Item $magentoOrderItem)
    {
        $bundleOptions = $magentoOrderItem->getProductOptions()['bundle_options'] ?? [];

        $links = $this->getBundleLinksForNetSuite($magentoOrderItem->getProductId());
        foreach ($bundleOptions as $option) {
            $targetSku = $option['value'][0]['title'];
            $internalId = $links[$targetSku] ?? null;
            if ($internalId) {
                return $internalId;
            }
        }

        return null;
    }
}
