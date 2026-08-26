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

namespace MageOS\NetSuiteConnector\Product\Model\Mapper\Product;

use Magento\AdvancedPricingImportExport\Model\Import\AdvancedPricing;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\DataObject;
use NetSuite\Classes\RecordType;
use MageOS\NetSuiteConnector\Core\Model\ImportRowList;

class KitItem extends \MageOS\NetSuiteConnector\Product\Model\Mapper\Product
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
        private readonly \MageOS\NetSuiteConnector\Product\Model\Service\CustomerGroupProvider $customerServiceProvider
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
        return $inventoryItem instanceof \NetSuite\Classes\KitItem ? Type::TYPE_BUNDLE : Type::TYPE_SIMPLE;
    }

    /**
     * @param $magentoProduct
     * @param $inventoryItem
     * @return \Magento\Framework\DataObject
     * @throws \Exception
     */
    public function setProductType($magentoProduct, $inventoryItem): DataObject
    {
        if ($inventoryItem instanceof \NetSuite\Classes\InventoryItem) {
            return parent::setProductData($magentoProduct, $inventoryItem);
        }
        $magentoProduct = $this->setBundleData($magentoProduct, $inventoryItem);
        return $magentoProduct;
    }

    public function getPricing($magentoProduct, $inventoryItem): array
    {
        $tierPrices = [];

        if ($inventoryItem->pricingMatrix && \is_array($inventoryItem->pricingMatrix->pricing)) {
            $this->setBasePrice($magentoProduct, $inventoryItem);

            $tierPrices = $this->getTierPrices($inventoryItem, $magentoProduct);
        }

        if (!$magentoProduct->getPrice()) {
            $magentoProduct->setPrice('0.00');
        }

        return \count($tierPrices) ? $tierPrices : [];
    }

    /**
     * @param DataObject $magentoProduct
     * @param $inventoryItem \NetSuite\Classes\KitItem
     * @return DataObject
     * @throws \Exception
     */
    protected function setBundleData(DataObject $magentoProduct, $inventoryItem)
    {
        $displayName = null;
        if (isset($inventoryItem->displayName)) {
            $displayName = trim($inventoryItem->displayName);
        }
        if (!empty($displayName)) {
            $magentoProduct->setName($inventoryItem->displayName);
        }
        if (empty($displayName)) {
            $magentoProduct->setName($inventoryItem->itemId);
        }

        $magentoProduct->setBundleSkuType('dynamic');

        $magentoProduct->setBundlePriceType('fixed');

        $magentoProduct->setBundleWeightType('dynamic');
        $magentoProduct->setBundleShipmentType('together');
        $magentoProduct->setBundlePriceView("As low as");

        $magentoProduct->setBundleParentId($inventoryItem->itemId);

        $productIdsMap = $this->prefetchProcessingItem->getBundleMemberProducts($inventoryItem->memberList->itemMember);
        $unresolvedIds = [];
        foreach (array_keys($productIdsMap) as $netsuiteInternalId) {
            if ($productIdsMap[$netsuiteInternalId] !== null) {
                continue;
            }
            if (!$this->importManager->isProductInQueue($netsuiteInternalId)) {
                $unresolvedIds[] = $netsuiteInternalId;
            } else {
                $sku = $this->importManager->resolveSku($netsuiteInternalId);
                if (!$sku) {
                    throw new \RuntimeException("Can't resolve sku for ns#$netsuiteInternalId");
                }

                $productIdsMap[$netsuiteInternalId] = $sku;
            }
        }

        if (\count($unresolvedIds)) {
            $productIdsMap = $this->importUnresolvedIds($unresolvedIds, $productIdsMap);
        }

        $bundleProductOptions = [];
        foreach ($inventoryItem->memberList->itemMember as $itemMember) {
            $option = [];
            $option['required'] = 1;
            $option['name'] = $itemMember->item->name;
            $option['type'] = 'radio';
            $option['sku'] = $productIdsMap[$itemMember->item->internalId];
            $option['default_qty'] = $itemMember->quantity;
            $option['default'] = 1;
            $option['can_change_quantity'] = 0;

            // needed for the fixed price
            $option['price'] = 0;

            $bundleProductOptions[] = $option;
        }

        $magentoProduct->setBundleValues($bundleProductOptions);
        $magentoProduct->setIsInStock(1);

        return $magentoProduct;
    }

    /**
     * @param $internalIds
     * @return mixed
     * @throws \Exception
     */
    protected function importBundleMemberItems($internalIds)
    {
        $items = $this->serviceRepository->fetchMultipleRecordsFromNetSuite(RecordType::inventoryItem, $internalIds);
        return array_map([$this, 'mapInventoryItemToRowList'], $items);
    }

    /**
     * @param $magentoProduct
     * @param $inventoryItem
     */
    protected function setBasePrice($magentoProduct, $inventoryItem): void
    {
        $consideredPricingLevelId = $this->importConfig->getPriceLevelNetsuiteId();
        foreach ($inventoryItem->pricingMatrix->pricing as $pricingLevel) {
            if ($magentoProduct->getPrice()) {
                break;
            }

            $priceLevelId = $pricingLevel->priceLevel->internalId;
            if ($priceLevelId == $consideredPricingLevelId) {
                foreach ($pricingLevel->priceList->price as $priceItem) {
                    if ($priceItem->value && $priceItem->quantity <= 1) {
                        $magentoProduct->setPrice($priceItem->value);
                        break;
                    }
                }
            }
        }
    }

    /**
     * @param $inventoryItem
     * @param $magentoProduct
     * @param array $tierPrices
     * @return array
     */
    protected function getTierPrices($inventoryItem, $magentoProduct): array
    {
        $tierPrices = [];
        $customerGroup = $this->customerServiceProvider->getCustomerGroupTierPrices();
        $groupId = $this->importConfig->getTierPriceCustomerGroup();
        foreach ($inventoryItem->pricingMatrix->pricing as $pricingLevel) {
            // skip empty ones
            if (!$pricingLevel->priceList || empty($pricingLevel->priceList->price) || $customerGroup === null) {
                continue;
            }

            $customerGroupName = $this->getCustomerGroupName($groupId, $customerGroup);

            foreach ($pricingLevel->priceList->price as $priceItem) {
                if (!$priceItem->value || $priceItem->quantity <= 1) {
                    continue;
                }
                $tierPrice = $priceItem->value;

                // bundle products support only percentage
                if ($magentoProduct->getProductType() === 'bundle') {
                    $tierPrice = ($magentoProduct->getPrice() - $tierPrice) / ($magentoProduct->getPrice() / 100);
                }

                $tierPrices[] = [
                    AdvancedPricing::COL_SKU => $magentoProduct->getSku(),
                    AdvancedPricing::COL_TIER_PRICE_WEBSITE => 'All Websites [USD]',
                    AdvancedPricing::COL_TIER_PRICE_CUSTOMER_GROUP => $customerGroupName,
                    AdvancedPricing::COL_TIER_PRICE_QTY => $priceItem->quantity,
                    AdvancedPricing::COL_TIER_PRICE_TYPE => AdvancedPricing::TIER_PRICE_TYPE_FIXED,
                    AdvancedPricing::COL_TIER_PRICE => $tierPrice
                ];
            }
        }

        return $tierPrices;
    }

    /**
     * @param array $unresolvedIds
     * @param array $productIdsMap
     * @return array
     * @throws \Exception
     */
    protected function importUnresolvedIds(array $unresolvedIds, array $productIdsMap): array
    {
        // Product does not exist in Magento. Import it now.
        /** @var ImportRowList $importResult */
        $importResults = $this->importBundleMemberItems($unresolvedIds);

        foreach ($importResults as $importResult) {
            // push this item into current import/export result
            $this->importResult->mergeWith($importResult);

            $queue = $importResult->getEntityRows('catalog_product');
            if (\count($queue) > 0) {
                $productIdsMap[$queue[0]['netsuite_internal_id']] = $queue[0]['sku'];
            }
        }

        return $productIdsMap;
    }

    /**
     * @param string $groupId
     * @param $customerGroup
     * @return string
     */
    protected function getCustomerGroupName(string $groupId, $customerGroup): string
    {
        $customerGroupName = self::CUST_ALL_GROUPS_NAME;
        if (self::CUST_ALL_GROUPS_ID != $groupId) {
            $customerGroupName = $customerGroup->getCode();
        }

        return $customerGroupName;
    }
}
