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

namespace MageOS\NetSuiteConnector\Product\Model;

use NetSuite\Classes\RecordType;
use MageOS\NetSuiteConnector\Core\Model\ImportQueueManager;
use MageOS\NetSuiteConnector\Product\Model\Process\Import\Item;

/**
 * Prefetch is used for fetching related/associated items before importing the actual item to
 * keep data integrity.
 */
class Prefetch
{
    /**
     * @var ImportQueueManager
     */
    private $importManager;

    /**
     * @var Item
     */
    private $inventoryItem;
    /**
     * @var \MageOS\NetSuiteConnector\Product\Model\ResourceModel\Repository
     */
    private $netsuiteProductRepository;
    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\NetSuite\ServiceRepository
     */
    private $serviceRepository;

    /**
     * Prefetch constructor.
     * @param Item $inventoryItem
     * @param ImportQueueManager $importManager
     * @param \MageOS\NetSuiteConnector\Product\Model\ResourceModel\Repository $netsuiteProductRepository
     * @param \MageOS\NetSuiteConnector\Core\Model\NetSuite\ServiceRepository $serviceRepository
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Product\Model\Process\Import\Item $inventoryItem,
        \MageOS\NetSuiteConnector\Core\Model\ImportQueueManager $importManager,
        \MageOS\NetSuiteConnector\Product\Model\ResourceModel\Repository $netsuiteProductRepository,
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\ServiceRepository $serviceRepository
    ) {
        $this->importManager = $importManager;
        $this->inventoryItem = $inventoryItem;
        $this->netsuiteProductRepository = $netsuiteProductRepository;
        $this->serviceRepository = $serviceRepository;
    }

    /**
     * Returns internal ids mapped to SKUs: [internal_id => sku]
     * @param array $nsIds
     * @return array
     * @throws \Exception
     */
    public function prefetchAndMap(array $nsIds): array
    {
        $productMap = [];
        list($fetchFromDBIds, $fetched) = $this->prefetch($nsIds, $productMap);

        if (count($fetchFromDBIds) == 0) {
            return $productMap;
        }

        $productData = $this->netsuiteProductRepository->fetchProductDataForPrefetch($fetchFromDBIds);
        $productMap += $productData;

        $missingIds = $this->getMissingIds($nsIds, $productMap);
        if (count($missingIds) == 0) {
            return $productMap;
        }

        $missingIds = array_diff($missingIds, $fetched);
        $items = $this->serviceRepository->fetchMultipleRecordsFromNetSuite(RecordType::inventoryItem, $missingIds);

        try {
            /** @var \NetSuite\Classes\InventoryItem $item */
            foreach ($items as $item) {
                $rows = $item ? $this->inventoryItem->process($item) : null;
                if (!$rows) {
                    // not importable item!
                    $message = $item ? 'Tried to prefetch non-importable item#' . $item->internalId :
                        'Tried to prefetch non-existing item';
                    //$this->netsuiteHelper->log($message);
                    continue;
                }

                $this->importManager->import($rows);

                $products = $rows->getRawEntityData('catalog_product');
                $this->mapProducts($products, $missingIds, $productMap);
            }
        } catch (\Exception $e) { // phpcs:ignore
            //$this->netsuiteHelper->log($e->getMessage());
        }

        return $productMap;
    }

    /**
     * @param array $nsIds
     * @param array $productMap
     * @return array
     */
    protected function prefetch(array $nsIds, array &$productMap): array
    {
        $fetchFromDBIds = [];
        $fetched = [];

        foreach ($nsIds as $id) {
            if (!$this->importManager->isProductInQueue($id)) {
                $fetchFromDBIds[] = $id;
                continue;
            }

            $fetched[] = $id;
            $product = $this->importManager->getProductRowById($id);
            if ($product['product_online'] != '1') {
                continue;
            }

            $productMap[$id] = [
                'name'  => $product['name'] ?? '',
                'sku'   => $product['sku'],
                'price' => $product['price'] ?? '',
            ];
        }

        return [$fetchFromDBIds, $fetched];
    }

    /**
     * @param array $nsIds
     * @param array $productMap
     * @return array
     */
    protected function getMissingIds(array $nsIds, array $productMap): array
    {
        // check if we miss products
        $missingIds = [];
        foreach ($nsIds as $id) {
            if (!isset($productMap[$id])) {
                $missingIds[] = $id;
            }
        }

        return $missingIds;
    }

    /**
     * @param $products
     * @param array $missingIds
     * @param array $productMap
     */
    protected function mapProducts($products, array $missingIds, array &$productMap): void
    {
        foreach ($products as $product) {
            $id = $product['netsuite_internal_id'];
            if (in_array($id, $missingIds, true)) {
                $productMap[$id] = [
                    'name'  => $product['name'],
                    'sku'   => $product['sku'],
                    'price' => $product['price'],
                ];
            }
        }
    }
}
