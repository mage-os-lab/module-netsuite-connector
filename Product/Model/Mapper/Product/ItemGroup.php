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

use Magento\Framework\DataObject;
use NetSuite\Classes\ItemSearchBasic;
use NetSuite\Classes\RecordRef;
use NetSuite\Classes\RecordType;
use NetSuite\Classes\SearchMultiSelectField;
use NetSuite\Classes\SearchMultiSelectFieldOperator;
use NetSuite\Classes\SearchRequest;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class ItemGroup extends \MageOS\NetSuiteConnector\Product\Model\Mapper\Product
{
    private array $memberInventoryItems = [];
    private array $productIdsAndQty = [];
    private \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement;

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
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement,
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Repository $serviceRepository
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

        $this->serviceManagement = $serviceManagement;
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
        $this->loadMemberItems($inventoryItem);
        return $magentoProduct;
    }

    private function loadMemberItems($inventoryItem)
    {
        $this->productIdsAndQty = [];
        foreach ($inventoryItem->memberList->itemMember as $itemMem) {
            $this->productIdsAndQty[$itemMem->item->internalId] = $itemMem->quantity;
        }

        $this->memberInventoryItems = $this->prefetchItems(array_keys($this->productIdsAndQty));
    }

    public function getPricing($magentoProduct, $inventoryItem): array
    {
        $tierPrice = []; //not calculated for itemGroup

        $itemGroupPrice = 0.00;
        $consideredPricingLevelId = $this->importConfig->getPriceLevelNetsuiteId();
        foreach ($this->memberInventoryItems as $item) {
            if (empty($item->pricingMatrix) || is_array($item->pricingMatrix->pricing)) {
                continue;
            }
            foreach ($item->pricingMatrix->pricing as $pricingLevel) {
                $priceLevelId = $pricingLevel->priceLevel->internalId;
                if ($priceLevelId != $consideredPricingLevelId) {
                    continue;
                }
                foreach ($pricingLevel->priceList->price as $priceItem) {
                    if ($priceItem->quantity <= 1) {
                        $itemGroupPrice += $priceItem->value * ($this->productIdsAndQty[$item->internalId] ?? 1);
                        break;
                    }
                }
            }
        }

        $magentoProduct->setPrice($itemGroupPrice);
        return $tierPrice;
    }

    /**
     * fetching memeber products including all fields
     * @param array $ids
     * @return array
     */
    private function prefetchItems(array $ids)
    {
        if (empty($ids)) {
            return [];
        }

        $service = $this->serviceManagement->get();

        $tranSearchBasic = new ItemSearchBasic();
        $tranSearchBasic->internalId = new SearchMultiSelectField();
        $tranSearchBasic->internalId->operator = SearchMultiSelectFieldOperator::anyOf;

        foreach ($ids as $netsuiteInternalId) {
            $internalIdField = new RecordRef();
            $internalIdField->internalId = $netsuiteInternalId;
            $internalIdField->type = RecordType::inventoryItem;

            $tranSearchBasic->internalId->searchValue[] = $internalIdField;
        }
        $searchRequest = new SearchRequest();
        $searchRequest->searchRecord = $tranSearchBasic;

        $service->setSearchPreferences(false);
        $response = $service->search($searchRequest);

        if ($response->searchResult->status->isSuccess) {
            return $response->searchResult->recordList->record;
        } else {
            //phpcs:ignore
            throw new \RuntimeException((string)print_r($response->searchResult->status->statusDetail, true));
        }
    }
}
