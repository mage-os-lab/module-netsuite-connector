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
declare(strict_types=1);

namespace MageOS\NetSuiteConnector\Product\Model\Mapper;

use Magento\Framework\DataObject;
use Magento\Framework\Phrase;
use MageOS\NetSuiteConnector\Product\Model\Product\Map\Value;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Api\Data\ProductInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BaseData
{
    /**
     * @var array
     */
    private $attributeCache = [];
    /**
     * @var array
     */
    private $optionCache = [];
    /**
     * @var array
     */
    private $attributeLoaded = [];
    /**
     * @var \MageOS\NetSuiteConnector\Product\Model\Config\ProductConfig
     */
    private $productConfig;
    /**
     * @var \Magento\Store\Api\WebsiteRepositoryInterface
     */
    private $websiteRepository;
    /**
     * @var \Magento\Tax\Api\TaxClassRepositoryInterface
     */
    private $taxClassRepository;
    /**
     * @var \Magento\Store\Api\StoreRepositoryInterface
     */
    private $storeRepository;
    /**
     * @var \Magento\Catalog\Api\AttributeSetRepositoryInterface
     */
    private $attributeSetRepository;
    /**
     * @var \MageOS\NetSuiteConnector\Product\Model\Product\Map\ValueFactory
     */
    private $valueFactory;
    /**
     * @var \Magento\Eav\Model\Entity\Attribute
     */
    private $attributeModel;
    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory
     */
    private $optionCollectionFactory;
    /**
     * @var \MageOS\NetSuiteConnector\Core\Helper\EavHelper
     */
    private $eavHelper;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $timezoneInterface;

    /**
     * @var \MageOS\NetSuiteConnector\Product\Model\ResourceModel\Repository
     */
    private $netsuiteProductRepository;

    /**
     * BaseData constructor.
     * @param \MageOS\NetSuiteConnector\Product\Model\Config\ProductConfig $productConfig
     * @param \MageOS\NetSuiteConnector\Product\Model\Product\Map\ValueFactory $valueFactory
     * @param \MageOS\NetSuiteConnector\Core\Helper\EavHelper $eavHelper
     * @param \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepository
     * @param \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassRepository
     * @param \Magento\Store\Api\StoreRepositoryInterface $storeRepository
     * @param \Magento\Catalog\Api\AttributeSetRepositoryInterface $attributeSetRepository
     * @param \Magento\Eav\Model\Entity\Attribute $attributeModel
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $optionCollectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface
     * @param \MageOS\NetSuiteConnector\Product\Model\ResourceModel\Repository $netsuiteProductRepository
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Product\Model\Config\ProductConfig $productConfig,
        \MageOS\NetSuiteConnector\Product\Model\Product\Map\ValueFactory $valueFactory,
        \MageOS\NetSuiteConnector\Core\Helper\EavHelper $eavHelper,
        \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepository,
        \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassRepository,
        \Magento\Store\Api\StoreRepositoryInterface $storeRepository,
        \Magento\Catalog\Api\AttributeSetRepositoryInterface $attributeSetRepository,
        \Magento\Eav\Model\Entity\Attribute $attributeModel,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $optionCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \MageOS\NetSuiteConnector\Product\Model\ResourceModel\Repository $netsuiteProductRepository
    ) {
        $this->productConfig = $productConfig;
        $this->websiteRepository = $websiteRepository;
        $this->taxClassRepository = $taxClassRepository;
        $this->storeRepository = $storeRepository;
        $this->attributeSetRepository = $attributeSetRepository;
        $this->valueFactory = $valueFactory;
        $this->attributeModel = $attributeModel;
        $this->optionCollectionFactory = $optionCollectionFactory;
        $this->eavHelper = $eavHelper;
        $this->timezoneInterface = $timezoneInterface;
        $this->netsuiteProductRepository = $netsuiteProductRepository;
    }

    public function createProduct($inventoryItem): DataObject
    {
        $existProduct = null;
        $magentoProduct = new DataObject();

        if ($inventoryItem->internalId) {
            $collection = $this->netsuiteProductRepository->loadProductsByNetSuiteId($inventoryItem->internalId);
            if ($collection) {
                $existProduct = array_shift($collection);
            }
        }
        $defaultProductFields = $this->getDefaultProductFields($existProduct);
        $magentoProduct = $this->addDefaultFieldsForProduct($magentoProduct, $defaultProductFields);

        if (!$magentoProduct->getName()) {
            $names = [
                $inventoryItem->displayName,
                $inventoryItem->storeDisplayName,
                $inventoryItem->itemId,
            ];

            $magentoProduct->setName(current(array_filter($names)));
        }

        $this->setCustomValues($magentoProduct, $inventoryItem);

        return $magentoProduct;
    }

    /**
     * method
     * 1. strips redundant tags from attributes
     * 2. strips all styles inside tags ('/(<[^>]+) style=".*?"/i')
     * @param $magentoProduct
     * @return void
     */
    public function stripTagsForAddAttr($magentoProduct): void
    {
        $additionalAttr = $magentoProduct->getData('additional_attributes');
        $settings = $this->productConfig->getHtmlTags();
        if (!empty($additionalAttr) && !empty($settings) && is_array($additionalAttr)) {
            foreach ($settings as $setting) {
                if (isset($additionalAttr[$setting['attribute']])) {
                    $additionalAttr[$setting['attribute']] = strip_tags(
                        $additionalAttr[$setting['attribute']],
                        str_replace(',', '', $setting['html_tags'])
                    );
                    $additionalAttr[$setting['attribute']] = preg_replace(
                        '/(<[^>]+) style=".*?"/i',
                        '$1',
                        $additionalAttr[$setting['attribute']]
                    );
                }
            }
            $magentoProduct->setData('additional_attributes', $additionalAttr);
        }
    }

    private function getProductMapValue($fieldData, $customValues): array
    {
        $customValueListKey = Value::getCustomValueListKey($fieldData['magento']);

        if (isset($customValues[$customValueListKey])) {
            $productMapValue = $customValues[$customValueListKey];
            $productMapValue->addDefaultValue($fieldData['netsuite'], $fieldData['netsuite_field_value']);
        } else {
            if ($fieldData['netsuite_settings'] === 'custom_checkbox') {
                $netsuiteFieldValue = !empty($fieldData['netsuite_field_value']) ?
                    $fieldData['netsuite_field_value'] : 1;
            } else {
                $netsuiteFieldValue = $fieldData['netsuite_list_id'];
            }

            /** @var Value $productMapValue */
            $productMapValue = $this->valueFactory->create(
                $fieldData['magento'],
                $fieldData['netsuite'],
                $fieldData['netsuite_settings'],
                $netsuiteFieldValue,
                isset($fieldData['netsuite_list_id']) ? $fieldData['netsuite_list_id'] : 0
            );

            if ($fieldData['netsuite_settings'] === 'constant_magento_value') {
                if (empty($fieldData['netsuite_field_value'])) {
                    return [];
                }

                $productMapValue->setConstantMagentoValue($fieldData['netsuite_field_value']);
            }
        }

        return [$customValueListKey, $productMapValue];
    }

    /**
     * @param $magentoProduct
     * @param $inventoryItem
     */
    public function setCustomValues($magentoProduct, $inventoryItem)
    {
        $fieldMap = $this->productConfig->getFieldMap();
        $customValues = [];

        foreach ($fieldMap as $fieldData) {
            if (!isset($fieldData['magento'])) {
                continue;
            }

            $customData = $this->getProductMapValue($fieldData, $customValues);
            if (count($customData) == 0) {
                continue;
            }
            /** @var Value $productMapValue */
            list($customValueListKey, $productMapValue) = $customData;
            $customValues[$customValueListKey] = $productMapValue;

            /**
             * Multiple Netsuite fields can be pushed to a single Magento attribute.
             * They are defined delimited by comma, i.e. custitem_primary_color,custitem_secondary_color
             */
            $netsuiteFields = $this->getMultipleNetSuiteFields($fieldData, $productMapValue, $inventoryItem);
            if (\count($netsuiteFields) > 1) {
                if ($this->sequenceIsEmpty($productMapValue)) {
                    $productMapValue->setValue(null);
                } else {
                    $this->mergeFieldValues($productMapValue, $netsuiteFields);
                }
            }
        }

        $this->setProductValues($customValues, $magentoProduct, $inventoryItem);

        if (!$magentoProduct->getSku()) {
            throw new \InvalidArgumentException("InventoryItem doesn't have SKU set.");
        }
    }

    public function getDefaultProductFields(?ProductInterface $existProduct): array
    {
        $defaultProductFields = [];
        $defaultProductFields['visibility'] = $existProduct ? (int)$existProduct->getVisibility() : 0;
        $defaultProductFields['status'] = $existProduct
            ? $existProduct->getStatus() : $this->productConfig->getDefaultStatus();
        $defaultProductFields['website_ids'] = $existProduct ? $existProduct->getWebsiteIds() : [];
        $defaultProductFields['tax'] = $existProduct ? (int)$existProduct->getTaxClassId() : null;
        $defaultProductFields['store_ids'] = $existProduct ? $existProduct->getStoreIds() : [];
        $defaultProductFields['attribute_set_id'] = $existProduct ? (int)$existProduct->getAttributeSetId() : 0;
        return $defaultProductFields;
    }

    public function addDefaultFieldsForProduct(DataObject $product, array $defaultProductFields): DataObject
    {
        $product->setVisibility($this->getProductVisibility($defaultProductFields['visibility']));
        $product->setProductOnline($defaultProductFields['status']);
        $product->setData('_product_websites', $this->getProductWebsites($defaultProductFields['website_ids']));
        $product->setTaxClassName($this->getTaxClassName($defaultProductFields['tax']));
        $product->setStoreViewCode($this->getStoreNames($defaultProductFields['store_ids']));
        $product->setAttributeSetCode($this->getAttributeSetName($defaultProductFields['attribute_set_id']));
        return $product;
    }

    protected function getProductWebsites(array $websiteIds): string
    {
        if (count($websiteIds) === 0) {
            $websiteIds = $this->productConfig->getDefaultWebsiteIds();
        }

        return $this->getWebsiteNames($websiteIds);
    }

    protected function getProductVisibility(int $visibilityId): string
    {
        if (!$visibilityId) {
            $visibilityId = $this->productConfig->getDefaultVisibility();
        }
        /** @var Phrase $visibility */
        $visibility = Visibility::getOptionText($visibilityId);
        return $visibility->getText();
    }

    /**
     * @param $idList
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getWebsiteNames($idList)
    {
        static $cache = [];

        $res = [];
        $ids = is_array($idList) ? $idList : explode(',', $idList);
        foreach ($ids as $id) {
            if (isset($cache[$id])) {
                $res[] = $cache[$id];
            } else {
                $webSite = $this->websiteRepository->getById($id);
                $name = $webSite->getCode();
                $cache[$id] = $name;
                $res[] = $name;
            }
        }

        return implode(',', $res);
    }

    protected function getTaxClassName(?int $taxClassId): string
    {
        if (!$taxClassId) {
            $taxClassId = $this->productConfig->getDefaultTaxClassId();
        }

        if (!$taxClassId) {
            return '';
        }

        try {
            $taxClass = $this->taxClassRepository->get($taxClassId);
            if ($taxClass) {
                return $taxClass->getClassName();
            }
            //phpcs:ignore
        } catch (\Exception $e) {

        }

        return '';
    }

    protected function getStoreNames(array $ids): string
    {
        static $storeNames = null;
        if ($storeNames !== null) {
            return $storeNames;
        }

        $storeList = [];
        if (count($ids) === 0) {
            $ids = explode(',', $this->productConfig->getDefaultStoreIds());
        }

        foreach ($ids as $id) {
            $storeList[] = $this->storeRepository->getById($id)->getCode();
        }

        $storeNames = implode(',', $storeList);
        return $storeNames;
    }

    protected function getAttributeSetName(int $attributeSetId): string
    {
        if (!$attributeSetId) {
            $attributeSetId = $this->productConfig->getDefaultAttributeSetId();
        }

        return $this->attributeSetRepository->get($attributeSetId)->getAttributeSetName();
    }

    /**
     * When multiple fields are merged into one attribute, we need to keep the empty values too to preserve field order.
     * But this leads to the case where all values are empty, in which the field combination is to be ignored.
     * This method detects whether given a value sequence, all are empty or not.
     *
     * @param $productMapValue
     * @return bool
     */
    protected function sequenceIsEmpty($productMapValue)
    {
        $values = $productMapValue->getValues();
        $sequenceIsEmpty = true;
        foreach ($values as $value) {
            if (trim((string)$value)) {
                $sequenceIsEmpty = false;
                break;
            }
        }
        return $sequenceIsEmpty;
    }

    /**
     * Merges multiple Netsuite fields into a single string. By default,
     * it will just merge them using a space, i.e. value1 value2
     * Method is public to allow Plugin usage if different merge style is needed
     *
     * @param $productMapValue
     * @param $netsuiteFields
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function mergeFieldValues($productMapValue, $netsuiteFields)
    {
        $mergedString = implode(' ', $productMapValue->getValue());
        $productMapValue->setValue([$mergedString]);
    }

    /**
     * @param $productMapValue
     * @param $magentoProduct
     * @param string $internalId
     * @return null|string
     *
     * internalId is not used as it was used in logging. This will be removed once Logging logic is implemented
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getValueForMagento($productMapValue, $magentoProduct, $internalId = '')
    {
        $attribute = $this->getAttributeByCode($productMapValue->getMagentoFieldId());

        $frontEndInput = $attribute->getFrontendInput();
        if (\is_object($attribute) && \in_array($frontEndInput, ['select', 'multiselect'])) {
            return $this->getValueForMagentoSelect($productMapValue, $magentoProduct, $frontEndInput);
        }
        if ($frontEndInput === 'boolean') {
            $values = $productMapValue->getValues();
            return ($values[0] ?? 0) ? 'Yes' : 'No';
        }
        if ($frontEndInput === 'date') {
            $values = $productMapValue->getValues();

            if (count($values) == 0) {
                return null;
            }

            return $this->timezoneInterface->date(
                new \DateTime($values[0], new \DateTimeZone($this->timezoneInterface->getDefaultTimezone())),
                null,
                false
            )
                ->add(new \DateInterval('P1D'))
                ->format('m/d/Y');
        }

        $values = $productMapValue->getValues();
        return $values[0] ?? null;
    }

    protected function getValueForMagentoSelect($productMapValue, $magentoProduct, $frontEndInput)
    {
        $values = $productMapValue->getValues();
        $selectedOptionIds = [];

        if (is_array($values)) {
            $values = array_unique($values);

            if ($frontEndInput === 'select' &&
                $magentoProduct->getProductType() === 'simple' &&
                \count($values) > 1
            ) {
                // select can have only one value
                $values = \array_slice($values, 0, 1);
                /*
                $this->netsuiteHelper->log(
                'Warning: more than one value specified for [select] attribute '
                 . $attribute->getAttributeCode() . ', NS#' . $internalId
                );
                */
            }

            foreach ($values as $netsuiteValue) {
                if (is_object($netsuiteValue)) {
                    $netsuiteValue = $netsuiteValue->name;
                }
                if (!$this->optionLabelExists($productMapValue->getMagentoFieldId(), $netsuiteValue)) {
                    // create new option value if needed
                    $this->addOptionToAttribute($productMapValue->getMagentoFieldId(), $netsuiteValue);
                }
                $selectedOptionIds[] = htmlentities($netsuiteValue);
            }
            return implode(',', array_unique($selectedOptionIds));
        } else {
            return null;
        }
    }

    /**
     * @param $fieldName
     * @param $fieldValue
     * @param \Magento\Framework\DataObject $product
     */
    public function setProductFieldValue(string $fieldName, $fieldValue, DataObject $product)
    {
        $addAttributes = (array)$product->getAdditionalAttributes();
        $addAttributes[$fieldName] = $fieldValue;
        $product->setAdditionalAttributes($addAttributes);
    }

    /**
     * @param $attributeCode
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAttributeByCode($attributeCode)
    {
        if (!isset($this->attributeCache[$attributeCode])) {
            $this->attributeCache[$attributeCode] = clone $this->attributeModel->loadByCode(
                \Magento\Catalog\Model\Product::ENTITY,
                $attributeCode
            );
        }
        return $this->attributeCache[$attributeCode];
    }

    /**
     * @param $attributeCode
     * @param $value
     * @throws \DomainException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function addOptionToAttribute($attributeCode, $value)
    {
        $attribute = $this->getAttributeByCode($attributeCode);
        $attributeId = $attribute->getAttributeId();
        $label = htmlentities($value);

        $option['attribute_id'] = $attributeId;
        $option['attribute_code'] = $attributeCode;
        $option['value']['co_' . $value][0] = $label;

        $this->eavHelper->addAttributeOption($attribute, $option);

        $this->optionCache[$attributeCode . '_' . $label] = true;
        unset($this->attributeLoaded[$attributeCode]);
    }

    /**
     * @param string $attributeCode
     * @param $label
     * @return bool|mixed
     */
    protected function optionLabelExists(string $attributeCode, $label)
    {
        if (is_object($label)) {
            $label = $label->name;
        }
        $label = htmlentities($label);
        $cacheKey = $attributeCode . '_' . $label;

        if (isset($this->optionCache[$cacheKey])) {
            return $this->optionCache[$cacheKey];
        }

        if (!isset($this->attributeLoaded[$attributeCode])) {
            $options = $this->getAllOptionsForAttribute($attributeCode);
            $this->attributeLoaded[$attributeCode] = true;

            foreach ($options as $option) {
                $newCacheKey = $attributeCode . '_' . $option['label'];
                $this->optionCache[$newCacheKey] = true;
            }

            return $this->optionCache[$cacheKey] ?? false;
        }

        return false;
    }

    /**
     * @param $attributeCode
     * @return array
     */
    protected function getAllOptionsForAttribute($attributeCode)
    {
        $attribute = $this->attributeModel->loadByCode(\Magento\Catalog\Model\Product::ENTITY, $attributeCode);
        $options = $this->optionCollectionFactory
            ->create()
            ->setPositionOrder('asc')
            ->setAttributeFilter($attribute->getId())
            ->setStoreFilter($attribute->getStoreId())
            ->load()
            ->toOptionArray();
        return $options;
    }

    /**
     * @param $fieldData
     * @param $productMapValue
     * @param $inventoryItem
     * @return false|string[]
     */
    protected function getMultipleNetSuiteFields($fieldData, $productMapValue, $inventoryItem)
    {
        $netsuiteFields = explode(',', $fieldData['netsuite']);
        foreach ($netsuiteFields as $netsuiteField) {
            $netsuiteField = trim($netsuiteField);
            if ($netsuiteField) {
                if (\count($netsuiteFields) > 1) {
                    $productMapValue->extractValue(
                        $inventoryItem,
                        $netsuiteField,
                        false,
                        false
                    );
                } else {
                    $productMapValue->extractValue(
                        $inventoryItem,
                        $netsuiteField,
                        false,
                        true
                    );
                }
            }
        }

        return $netsuiteFields;
    }

    /**
     * @param array $customValues
     * @param $magentoProduct
     * @param $inventoryItem
     */
    protected function setProductValues(array $customValues, $magentoProduct, $inventoryItem): void
    {
        foreach ($customValues as $productMapValue) {
            $valueForMagento = $this->getValueForMagento($productMapValue, $magentoProduct, $inventoryItem->internalId);
            $fieldId = $productMapValue->getMagentoFieldId();

            if ($fieldId === 'sku' && $valueForMagento !== null) {
                $magentoProduct->setSku($valueForMagento);
            } else {
                if ($fieldId === 'name') {
                    if ($valueForMagento !== null) {
                        $magentoProduct->setName($valueForMagento);
                    }
                } else {
                    // skip empty strings only and null's
                    //|| (is_string($valueForMagento) && empty($valueForMagento))
                    if ($valueForMagento === null) {
                        $valueForMagento = '';
                    }

                    $this->setProductFieldValue($fieldId, $valueForMagento, $magentoProduct);
                }
            }
        }
    }
}
