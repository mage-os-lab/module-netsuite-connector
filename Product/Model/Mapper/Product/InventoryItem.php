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
use Magento\Framework\DataObject;
use MageOS\NetSuiteConnector\Product\Model\Config\Source\SpecialPrice;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InventoryItem extends \MageOS\NetSuiteConnector\Product\Model\Mapper\Product
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
        return \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE;
    }

    /**
     * @param $magentoProduct
     * @param $inventoryItem
     * @return \Magento\Framework\DataObject
     */
    public function setProductType($magentoProduct, $inventoryItem): DataObject
    {
        return $magentoProduct;
    }

    public function getPricing($magentoProduct, $inventoryItem): array
    {
        $tierPrices = [];

        if ($inventoryItem->pricingMatrix && \is_array($inventoryItem->pricingMatrix->pricing)) {
            $this->setBasePrice($magentoProduct, $inventoryItem);
            $this->setSpecialPrice($magentoProduct, $inventoryItem);

            $tierPrices = $this->getTierPrices($magentoProduct, $inventoryItem);
        }

        if (!$magentoProduct->getPrice()) {
            $magentoProduct->setPrice('0.00');
        }

        return \count($tierPrices) ? $tierPrices : [];
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
     * @param $magentoProduct
     * @param $inventoryItem
     * @return void
     */
    protected function setSpecialPrice($magentoProduct, $inventoryItem): void
    {
        $specialPriceBehavior = $this->importConfig->getImportSpecialPrice();
        $specialPriceId = $this->importConfig->getSpecialPricePriceLevel();
        if ($specialPriceBehavior === SpecialPrice::OPTION_NO ||
            ($specialPriceBehavior === SpecialPrice::OPTION_UPDATE && empty($specialPriceId))) {
            return;
        } elseif ($specialPriceBehavior === SpecialPrice::OPTION_REPLACE && empty($specialPriceId)) {
            $magentoProduct->setSpecialPrice(null);
            return;
        }

        foreach ($inventoryItem->pricingMatrix->pricing as $pricingLevel) {
            $priceLevelId = $pricingLevel->priceLevel->internalId;
            if ($priceLevelId == $specialPriceId) {
                foreach ($pricingLevel->priceList->price as $priceItem) {
                    if ($priceItem->quantity <= 1) {
                        if ($priceItem->value || $specialPriceBehavior === SpecialPrice::OPTION_REPLACE) {
                            $magentoProduct->setSpecialPrice($priceItem->value);
                        }
                        break;
                    }
                }
            }
        }
    }

    /**
     * @param $magentoProduct
     * @param $inventoryItem
     * @return array
     */
    protected function getTierPrices($magentoProduct, $inventoryItem): array
    {
        $tierPrices = [];

        $customerGroup = $this->customerServiceProvider->getCustomerGroupTierPrices();
        $groupId = $this->importConfig->getTierPriceCustomerGroup();
        foreach ($inventoryItem->pricingMatrix->pricing as $pricingLevel) {
            // skip empty ones
            if (!$pricingLevel->priceList || empty($pricingLevel->priceList->price) ||
                ($customerGroup === null && self::CUST_ALL_GROUPS_ID != $groupId)) {
                continue;
            }

            $customerGroupName = self::CUST_ALL_GROUPS_NAME;
            if (self::CUST_ALL_GROUPS_ID != $groupId) {
                $customerGroupName = $customerGroup->getCode();
            }

            foreach ($pricingLevel->priceList->price as $priceItem) {
                if (!$priceItem->value || $priceItem->quantity <= 1) {
                    continue;
                }

                $tierPrices[] = [
                    AdvancedPricing::COL_SKU => $magentoProduct->getSku(),
                    AdvancedPricing::COL_TIER_PRICE_WEBSITE => 'All Websites [USD]',
                    AdvancedPricing::COL_TIER_PRICE_CUSTOMER_GROUP => $customerGroupName,
                    AdvancedPricing::COL_TIER_PRICE_QTY => (string)$priceItem->quantity,
                    AdvancedPricing::COL_TIER_PRICE_TYPE => AdvancedPricing::TIER_PRICE_TYPE_FIXED,
                    AdvancedPricing::COL_TIER_PRICE => (string)$priceItem->value
                ];
            }
        }

        return $tierPrices;
    }
}
