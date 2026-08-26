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
namespace MageOS\NetSuiteConnector\Product\Model\Mapper\Product;

use Magento\Framework\DataObject;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\CustomFieldAccess;
use MageOS\NetSuiteConnector\Core\Exception\SkipRecordException;

class MatrixItem extends \MageOS\NetSuiteConnector\Product\Model\Mapper\Product
{
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
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Repository $serviceRepository,
        private \MageOS\NetSuiteConnector\Product\Model\ResourceModel\Repository $netsuiteProductRepository
    ) {
        parent::__construct(
            $context,
            $dbConnection,
            $valueFactory,
            $importRowListFactory,
            $importManager,
            $eavHelper,
            $importConfig,
            $productPrefetch,
            $productBaseData,
            $prefetchProcessingItem,
            $serviceRepository
        );
    }

    /**
     * @param $inventoryItem
     * @return string
     */
    public function getProductType($inventoryItem): string
    {
        return \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE;
    }

    /**
     * @param $magentoProduct
     * @param $inventoryItem
     * @return \Magento\Framework\DataObject
     */
    public function setProductType($magentoProduct, $inventoryItem): DataObject
    {
        $magentoProduct = $this->setConfigurableData($magentoProduct, $inventoryItem);
        return $magentoProduct;
    }

    /**
     * @param DataObject $magentoProduct
     * @param $inventoryItem
     * @return DataObject
     * @throws \RuntimeException
     * @throws \Exception
     */
    protected function setConfigurableData(DataObject $magentoProduct, $inventoryItem)
    {
        $matrixItemIds = $this->prefetchProcessingItem->getAllMatrixItemInternalIds($inventoryItem);
        if (!\count($matrixItemIds)) {
            $magentoProduct->setSkipNetsuiteProcessing(true);
            return $magentoProduct;
        }

        // remove configurable attributes from additional_attributes
        $configurableAttributes = $this->getConfigurableAttributesFromMatrix($inventoryItem);

        if (empty($configurableAttributes)) {
            throw new SkipRecordException("NS#{$inventoryItem->internalId}/Magento/Matrix Attributes are empty");
        }

        $this->setAdditionalAttributes($magentoProduct, $configurableAttributes);

        // no configurable attributes set
        if (!$configurableAttributes) {
            $magentoProduct->setIsInStock(0);
            return $magentoProduct;
        }

        //Check if all the matrix ids exist. If any is missing, throw exception.
        list($fetchFromDBIds, $missingIds) = $this->getMatrixChildIds($matrixItemIds);

        $variations = [];
        $existingSkus = [];

        $associatedSimpleProducts = $this->netsuiteProductRepository->loadProductsByNetSuiteId($fetchFromDBIds);
        foreach ($associatedSimpleProducts as $associatedSimpleProduct) {
            $variation = [];
            $sku = $associatedSimpleProduct->getSku();

            if (isset($existingSkus[$sku])) {
                //$this->netsuiteHelper->log('Duplicate variations for SKU:' . $sku);
                continue;
            }

            if ($associatedSimpleProduct->getStatus() == 2 || empty($associatedSimpleProduct->getWebsiteIds())) {
                $this->importManager->queueSuperLinkForRemoval(
                    $associatedSimpleProduct->getSku(),
                    $magentoProduct->getSku()
                );

                continue;
            }

            $existingSkus[$sku] = 1;
            $variation['sku'] = $sku;

            $variation = $this->validateAssociatedProducts(
                $configurableAttributes,
                $associatedSimpleProduct,
                $variation
            );

            $variations[] = $variation;
        }

        // Fetch missing linked products from NetSuite and also from import queue and add variations
        if (\count($missingIds)) {
            $variations = $this->fetchMissingVariations(
                $missingIds,
                $magentoProduct,
                $configurableAttributes,
                $variations
            );
        }

        $magentoProduct->setConfigurableVariations($variations);
        return $magentoProduct;
    }

    /**
     * Fetch product rows from either global import queue or NetSuite
     * @param array $missingIds
     * @return array
     * @throws \Exception
     */
    public function fetchMissingProductRows(array $missingIds): array
    {
        list($resultingRows, $idsNotResolved) = $this->sortMissingIds($missingIds);

        if (!empty($idsNotResolved)) {
            $items = $this->serviceRepository->fetchMultipleRecordsFromNetSuite('inventoryItem', $idsNotResolved);
            foreach ($items as $item) {
                if ($item) {
                    $rowList = $this->prefetchProcessingItem->mapItemAndQueue($item);
                    if ($rowList) {
                        //phpcs:ignore
                        $resultingRows = array_merge($resultingRows, $rowList->getRawEntityData('catalog_product'));
                    }
                } else {
                    $rowListStr = '';
                    if ($missingIds && is_array($missingIds)) {
                        $rowListStr = implode(',', $missingIds);
                    }
                    //$this->netsuiteHelper->log("Missing item in fetchMissingProductRows($rowListStr)");
                }
            }
        }
        return $resultingRows;
    }

    /**
     * @param \NetSuite\Classes\InventoryItem $inventoryItem
     * @return array
     * @throws \Exception
     */
    protected function getConfigurableAttributesFromMatrix($inventoryItem): array
    {
        $attributes = [];
        $matrixAttrs = CustomFieldAccess::get($inventoryItem, 'custitem_magento_matrix_attrs');

        if (!$matrixAttrs) {
            return $attributes;
        }

        $configurableCustomFields = explode(',', $matrixAttrs);
        if (!\is_array($configurableCustomFields) || \count($configurableCustomFields) == 0) {
            return $attributes;
        }

        $fieldMap = $this->importConfig->getFieldMap();
        foreach ($configurableCustomFields as $configurableCustomField) {
            list($found, $attributes) = $this->getAttributeFromField($fieldMap, $configurableCustomField, $attributes);

            if (!$found) {
                throw new \RuntimeException("No mapping found for the field {$configurableCustomField}");
            }
        }

        return $attributes;
    }

    public function getPricing($magentoProduct, $inventoryItem): array
    {
        $magentoProduct->setPrice('');

        return [];
    }

    /**
     * @param DataObject $magentoProduct
     * @param array $configurableAttributes
     * @return array
     */
    protected function setAdditionalAttributes(DataObject $magentoProduct, array $configurableAttributes): void
    {
        $additionalAttributes = $magentoProduct->getAdditionalAttributes();
        foreach ($configurableAttributes as $attribute) {
            unset($additionalAttributes[$attribute->getAttributeCode()]);
        }

        $magentoProduct->setAdditionalAttributes($additionalAttributes);
    }

    /**
     * @param array $matrixItemIds
     * @return array[]
     */
    protected function getMatrixChildIds(array $matrixItemIds): array
    {
        $fetchFromDBIds = [];

        //Check if all the matrix ids exist. If any is missing, throw exception.
        $missingIds = [];
        $existingProducts = $this->netsuiteProductRepository->countProductsByNetSuiteIds($matrixItemIds);
        foreach ($matrixItemIds as $netSuiteId) {
            if (!isset($existingProducts[$netSuiteId]) ||
                $this->importManager->getProductRowById($netSuiteId)) {
                $missingIds[] = $netSuiteId;
            } else {
                $fetchFromDBIds[] = $netSuiteId;
            }
        }

        return [$fetchFromDBIds, $missingIds];
    }

    /**
     * @param array $configurableAttributes
     * @param \Magento\Catalog\Api\Data\ProductInterface $associatedSimpleProduct
     * @param array $variation
     * @return array
     */
    protected function validateAssociatedProducts(
        array $configurableAttributes,
        \Magento\Catalog\Api\Data\ProductInterface $associatedSimpleProduct,
        array $variation
    ): array {
        $hasAttributes = false;
        foreach ($configurableAttributes as $attribute) {
            $code = $attribute->getAttributeCode();
            if ($associatedSimpleProduct->getCustomAttribute($code)
                && $associatedSimpleProduct->getCustomAttribute($code)->getValue()
            ) {
                $customAttribute = $associatedSimpleProduct->getCustomAttribute($code);
                $optionId = $customAttribute->getValue();
                $label = $this->netsuiteProductRepository->getOptionLabelByOptionId($code, $optionId);
                if ($label !== null) {
                    $variation[$code] = $label;
                    $hasAttributes = true;
                }
                continue;
            }
            /**
             * In some cases, the attribute is not a custom attribute so we pull the data directly from the product
             */
            if ($associatedSimpleProduct->getData($code)) {
                $optionId = $associatedSimpleProduct->getData($code);
                $label = $this->netsuiteProductRepository->getOptionLabelByOptionId($code, $optionId);
                if ($label != null) {
                    $variation[$code] = $label;
                    $hasAttributes = true;
                }
                continue;
            }
        }

        if (!$hasAttributes) {
            throw new \RuntimeException(sprintf(
                'Associated simple product NS#%s doesn\'t have any of configurable attributes',
                $associatedSimpleProduct->getNetsuiteInternalId()
            ));
        }

        return $variation;
    }

    /**
     * @param array $missingIds
     * @param DataObject $magentoProduct
     * @param array $configurableAttributes
     * @param array $variations
     * @return array
     * @throws \Exception
     */
    protected function fetchMissingVariations(
        array $missingIds,
        DataObject $magentoProduct,
        array $configurableAttributes,
        array $variations
    ): array {
        $rows = $this->fetchMissingProductRows($missingIds);

        if (!$rows || empty($rows)) {
            return $variations;
        }

        foreach ($rows as $product) {
            if (isset($product['_incomplete']) && $product['_incomplete']) {
                continue;
            }
            if (!isset($product['additional_attributes'])) {
                if (!isset($product['netsuite_internal_id'])) {//multistore feature
                    continue;
                }
                throw new \RuntimeException("Product doesn't have additional_attributes");
            }

            if ($product['product_online'] == 2 || empty($product['_product_websites'])) {
                $this->importManager->queueSuperLinkForRemoval(
                    $product['sku'],
                    $magentoProduct->getSku()
                );

                continue;
            }

            $additionalAttributes = $product['additional_attributes'];

            $variation = [];
            $variation['sku'] = $product['sku'];

            list($hasAttributes, $variation) = $this->getAttributeVariation(
                $configurableAttributes,
                $additionalAttributes,
                $variation
            );

            if (!$hasAttributes) {
                throw new \RuntimeException(sprintf(
                    'Associated simple product NS#%s doesn\'t have any of configurable attributes',
                    $product['netsuite_internal_id']
                ));
            }

            $variations[] = $variation;
        }

        return $variations;
    }

    /**
     * @param array $missingIds
     * @return array[]
     */
    protected function sortMissingIds(array $missingIds): array
    {
        $resultingRows = [];
        $idsNotResolved = [];

        foreach ($missingIds as $netsuiteInternalId) {
            $row = $this->importManager->getProductRowById($netsuiteInternalId);
            if ($row) {
                $resultingRows[] = $row;
            } else {
                $idsNotResolved[] = $netsuiteInternalId;
            }
        }

        return [$resultingRows, $idsNotResolved];
    }

    /**
     * @param array $fieldMap
     * @param string $configurableCustomField
     * @param array $attributes
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getAttributeFromField(array $fieldMap, string $configurableCustomField, array $attributes): array
    {
        $found = false;
        foreach ($fieldMap as $fieldMapItem) {
            $netsuiteFields = explode(',', $fieldMapItem['netsuite']);
            if ($fieldMapItem['netsuite_settings'] !== 'standard_field' && \in_array(
                $configurableCustomField,
                $netsuiteFields
            )) {
                $found = true;
                $attribute = $this->productBaseData->getAttributeByCode($fieldMapItem['magento']);

                $exists = false;
                foreach ($attributes as $existingAttribute) {
                    if ($existingAttribute->getAttributeCode() === $attribute->getAttributeCode()) {
                        $exists = true;
                        break;
                    }
                }

                if (!$exists) {
                    $attributes[] = $attribute;
                }

                break;
            }
        }

        return [$found, $attributes];
    }

    /**
     * @param array $configurableAttributes
     * @param $additionalAttributes
     * @param array $variation
     * @return array
     */
    protected function getAttributeVariation(
        array $configurableAttributes,
        $additionalAttributes,
        array $variation
    ): array {
        $hasAttributes = false;
        foreach ($configurableAttributes as $attribute) {
            $code = $attribute->getAttributeCode();
            if (isset($additionalAttributes[$code])) {
                $variation[$code] = $additionalAttributes[$code];
                $hasAttributes = true;
            }
        }

        return [$hasAttributes, $variation];
    }
}
