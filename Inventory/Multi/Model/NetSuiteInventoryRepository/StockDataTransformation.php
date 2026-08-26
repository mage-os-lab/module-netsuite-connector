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

namespace MageOS\NetSuiteConnector\Inventory\Multi\Model\NetSuiteInventoryRepository;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use NetSuite\Classes\ItemSearchRow;
use NetSuite\Classes\ItemType;
use MageOS\NetSuiteConnector\Inventory\Model\NetSuiteInventoryRepository\AbstractTransformation;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\ResponseValidator;
use MageOS\NetSuiteConnector\Inventory\Model\NetSuiteInventoryRepository\StockDataTransformationInterface;

/**
 * This class is responsible for stock data transformation before importing from NS.
 * @suppressWarnings(PHPMD)
 */
class StockDataTransformation extends AbstractTransformation implements StockDataTransformationInterface
{
    private array $sourcesToLocation = [];
    private \MageOS\NetSuiteConnector\Inventory\Multi\Model\MagentoSourceRepository $magentoSourceRepository;

    public function __construct(
        \MageOS\NetSuiteConnector\Inventory\Model\Config\StockConfig $stockConfig,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \MageOS\NetSuiteConnector\Product\Model\ResourceModel\Repository $netsuiteProductRepository,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $magentoStockConfiguration,
        \MageOS\NetSuiteConnector\Inventory\Multi\Model\MagentoSourceRepository $magentoSourceRepository
    ) {
        parent::__construct(
            $stockConfig,
            $eventManager,
            $stockRegistry,
            $netsuiteProductRepository,
            $magentoStockConfiguration
        );
        $this->magentoSourceRepository = $magentoSourceRepository;
    }

    /**
     * Process NS search response with stock data
     *
     * @param $searchResponse
     * @throws \MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException
     * @throws \MageOS\NetSuiteConnector\Core\Exception\NetSuiteRuntimeException
     */
    protected function transformNetSuiteSearchResponse($searchResponse): void
    {
        ResponseValidator::validate($searchResponse);

        /**
         * Typecasting since searchRow can be null
         */
        $records = (array)$searchResponse->searchResult->searchRowList->searchRow;
        $this->prepareProductIds($records);
        $this->prepareSourcesMap($records);

        foreach ($records as $record) {
            $this->prepareMultiInventoryUpdate($record);
        }
    }

    /**
     * Retrieve stock data from NS item and push it into class variable
     *
     * Left this method as public for testing purposes
     *
     * @param $itemSearchRow
     * @return void
     */
    public function prepareMultiInventoryUpdate($itemSearchRow): void
    {
        $productInternalId = $itemSearchRow->basic->internalId[0]->searchValue->internalId;
        $locationNetsuiteId = $itemSearchRow->inventoryLocationJoin->internalId[0]->searchValue->internalId;

        if (!isset($this->netSuiteIdMap[$productInternalId])) {
            // item not imported
            return;
        }
        if (!isset($this->sourcesToLocation[$locationNetsuiteId])) {
            // location not imported
            return;
        }

        $this->getRowData($itemSearchRow);
    }
    /**
     * Retrieve and prepare stock data for single NS item
     *
     * @param $itemSearchRow
     */
    protected function getRowData($itemSearchRow)
    {
        $internalId = $itemSearchRow->basic->internalId[0]->searchValue->internalId;
        if (!$internalId) {
            return;
        }
        $magentoProductId = $this->netSuiteIdMap[$internalId]['entity_id'];
        $magentoProductType = $this->netSuiteIdMap[$internalId]['type_id'];
        if (!$magentoProductId) {
            return;
        }

        $row = [];
        if ($magentoProductType === Configurable::TYPE_CODE) {
            $row['use_config_manage_stock'] = 0;
            $row['is_in_stock'] = 1;
            $row['manage_stock'] = 0;
        } elseif (!empty($itemSearchRow->basic->type[0])
            && is_object($itemSearchRow->basic->type[0])
            && $itemSearchRow->basic->type[0]->searchValue === ItemType::_nonInventoryItem
        ) {
            $row['use_config_manage_stock'] = 0;
            $row['is_in_stock'] = 1;
            $row['manage_stock'] = 0;
        } else {
            $qty = $this->getQtyFromSearchRow($itemSearchRow);
            $row['qty'] = $qty;
            $row['is_in_stock'] =
                !$this->stockConfig->getChangeStockStatusUnderZero() || $qty > 0;
            $row['use_config_manage_stock'] = 1;
        }
        $source = $this->sourcesToLocation[$itemSearchRow->inventoryLocationJoin
            ->internalId[0]->searchValue->internalId];
        $this->stockData[$source->getSourceCode()][$this->netSuiteIdMap[$internalId]['sku']] = $row;
    }
    /**
     * find sources for given location ids.
     * NSC will import only data for the sources
     *
     * @param ItemSearchRow[] $records
     */
    private function prepareSourcesMap(array $records): void
    {
        foreach ($records as $record) {
            $locationNetsuiteId = $record->inventoryLocationJoin->internalId[0]->searchValue->internalId ?? null;
            $source = $this->magentoSourceRepository->getSourceByNetSuiteData(
                (int)$locationNetsuiteId,
                null
            );
            if (null !== $source) {
                $this->sourcesToLocation[$locationNetsuiteId] = $source;
            }
        }
    }
}
