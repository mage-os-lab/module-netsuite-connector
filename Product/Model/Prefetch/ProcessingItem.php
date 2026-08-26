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
namespace MageOS\NetSuiteConnector\Product\Model\Prefetch;

use NetSuite\Classes\Record;
use NetSuite\Classes\RecordType;
use MageOS\NetSuiteConnector\Core\Model\ImportRowList;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\CustomFieldAccess;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProcessingItem
{
    public function __construct(
        private \MageOS\NetSuiteConnector\Core\Model\ImportQueueManager $importManager,
        private \MageOS\NetSuiteConnector\Product\Model\Import\Item\Mapper $mapper,
        private \MageOS\NetSuiteConnector\Product\Model\Prefetch\ProductPrefetchIdSource $productPrefetch,
        private \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Repository $serviceRepository,
        private \MageOS\NetSuiteConnector\Product\Model\ResourceModel\Repository $netsuiteProductRepository
    ) {
    }

    /**
     * This method is invoked from cron import. Here we need to prefetch all the
     * products needed to do the import.
     *
     *
     * @param $records
     * @throws \Exception
     */
    public function prefetchProducts($records): void
    {
        if (empty($records)) {
            return;
        }
        // prefetch
        $prefetchIds = [];

        foreach ($records as $inventoryItem) {
            // phpcs:ignore
            $prefetchIds = array_merge(
                $prefetchIds,
                $this->getAllRelatedNonExistentIds($inventoryItem)
            );
        }

        $prefetchIds = array_unique(
            array_merge(
                $prefetchIds,
                $this->productPrefetch->execute($records)
            )
        );

        $missingIdsFiltered = [];
        foreach ($prefetchIds as $id) {
            if (!isset($fetched[$id])) {
                $missingIdsFiltered[] = $id;
            }
        }

        $this->prefetchAllIds($missingIdsFiltered);

        // we also should prefetch all the components after we would get all the kit items
    }

    /**
     * @param array $prefetchIds
     * @throws \Exception
     */
    public function prefetchAllIds(array $prefetchIds): void
    {
        if (empty($prefetchIds)) {
            return;
        }
        $batches = $this->serviceRepository->fetchMultipleRecordsFromNetSuite(
            RecordType::inventoryItem,
            $prefetchIds
        );
        foreach ($batches as $item) {
            if ($item) {
                // here we assume that no products passed to this method exist
                $this->mapItemAndQueue($item);
            }
        }
    }

    public function getAllRelatedNonExistentIds($inventoryItem): array
    {
        $matrixItemIds = $this->getAllMatrixItemInternalIds($inventoryItem);
        if (empty($matrixItemIds)) {
            return [];
        }

        $notInQueue = [];

        foreach ($matrixItemIds as $netsuiteInternalId) {
            if (!$this->importManager->isProductInQueue($netsuiteInternalId)) {
                $notInQueue[] = $netsuiteInternalId;
            }
        }

        $nonexistentIds = [];
        $existingProducts = $this->netsuiteProductRepository->countProductsByNetSuiteIds($notInQueue);
        foreach ($notInQueue as $netsuiteInternalId) {
            if (!isset($existingProducts[$netsuiteInternalId])) {
                $nonexistentIds[] = $netsuiteInternalId;
            }
        }

        return $nonexistentIds;
    }

    public function getAllMatrixItemInternalIds(Record $inventoryItem): array
    {
        $matrixItemInternalIds = [];

        $matrixChildren = CustomFieldAccess::get($inventoryItem, 'custitem_magento_matrix_children');

        if ($matrixChildren) {
            $tmpIds = explode(',', $matrixChildren);
            foreach ($tmpIds as $tmpId) {
                $tmpId = trim($tmpId);
                if (!empty($tmpId)) {
                    $matrixItemInternalIds[] = $tmpId;
                }
            }
        }

        if (!\count($matrixItemInternalIds)) {
            return [];
        }

        return $matrixItemInternalIds;
    }

    public function mapItemAndQueue($item): ?ImportRowList
    {
        $isMagentoImportable = $this->mapper->getInstance($item)->isMagentoImportable($item);
        if ($isMagentoImportable) {
            $rowList = $this->mapper->getInstance($item)->mapInventoryItemToRowList($item);
            $this->importManager->import($rowList);
            return $rowList;
        }

        return null;
    }

    public function getBundleMemberProducts(array $itemMemberList): array
    {
        $internalIds = [];
        $productMap = []; // netsuite_internal_id to magento_product_id map

        foreach ($itemMemberList as $itemMember) {
            /** @var \NetSuite\Classes\ItemMember $itemMember */
            $internalIds[] = $itemMember->item->internalId;
            //TODO: Why is this line needed??
            $productMap[$itemMember->item->internalId] = null;
        }

        $items = $this->netsuiteProductRepository->loadProductsByNetSuiteId($internalIds);

        foreach ($items as $item) {
            $netsuiteId = $item->getCustomAttribute('netsuite_internal_id')->getValue();
            $productMap[$netsuiteId] = $item->getSku();
        }

        return $productMap;
    }
}
