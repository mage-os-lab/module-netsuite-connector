<?php declare(strict_types=1);
/*
 *   RocketWeb
 *
 *  NOTICE OF LICENSE
 *
 *  This source file is subject to the Open Software License (OSL 3.0)
 *  that is bundled with this package in the file LICENSE.txt.
 *  It is also available through the world-wide-web at this URL:
 *  http://opensource.org/licenses/osl-3.0.php
 *
 *  @category  RocketWeb
 *  @package   MageOS_NetSuiteConnector
 *  @copyright Copyright (c) 2026 RocketWeb (http://rocketweb.com)
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  @author    Rocket Web Inc.
 *
 */

namespace MageOS\NetSuiteConnector\Inventory\Multi\Model;

/**
 * Class MultiStockRepository - this class encapsulates logic for multiple sources updating with compatible way
 * (for cases with one default stock)
 */
class MultiStockRepository implements \MageOS\NetSuiteConnector\Inventory\Model\MagentoInventoryRepositoryInterface
{
    private \Magento\CatalogInventory\Model\Indexer\Stock\Processor $indexerStock;
    private \Magento\Inventory\Model\SourceItemFactory $sourceItemFactory;
    private \Magento\Inventory\Model\SourceItem\Command\SourceItemsSave $saveSourceItem;

    /**
     * SingleStockRepository constructor.
     * @param \Magento\CatalogInventory\Model\Indexer\Stock\Processor $indexerStock
     * @param \Magento\Inventory\Model\SourceItem\Command\SourceItemsSave $saveSourceItem
     * @param \Magento\Inventory\Model\SourceItemFactory $sourceItemFactory
     */
    public function __construct(
        \Magento\CatalogInventory\Model\Indexer\Stock\Processor $indexerStock,
        \Magento\Inventory\Model\SourceItem\Command\SourceItemsSave $saveSourceItem,
        \Magento\Inventory\Model\SourceItemFactory $sourceItemFactory
    ) {
        $this->indexerStock = $indexerStock;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->saveSourceItem = $saveSourceItem;
    }

    /**
     * @inheritDoc
     */
    public function saveInventoryData(array $stockData, array $productIdsToReindex): bool
    {
        if (!empty($stockData)) {
            $sourceItemsForUpdate = $this->getSourcesForUpdate($stockData);
            //insert/update per 100 source items
            $batches = array_chunk($sourceItemsForUpdate, 100);
            foreach ($batches as $batch) {
                $this->saveSourceItem->execute($batch);
            }
            //price index reindex
            if (!empty($productIdsToReindex)) {
                $this->indexerStock->reindexList($productIdsToReindex);
            }
        }
        return true;
    }

    /**
     * method builds source Items updates their qty and status and return for saving
     * @param array $stockData
     * @return array
     */
    private function getSourcesForUpdate(array $stockData): array
    {
        $result = [];
        foreach ($stockData as $sourceCode => $source) {
            foreach ($source as $sku => $item) {
                $sourceItem = $this->sourceItemFactory->create();
                $sourceItem->setData('source_code', (string)$sourceCode);
                $sourceItem->setData('sku', (string)$sku);
                $sourceItem->setData(
                    'quantity',
                    sprintf("%.4f", $item['qty'])
                );
                $sourceItem->setData('status', (string)$item['is_in_stock']);
                $result [] = $sourceItem;
            }
        }

        return $result;
    }
}
