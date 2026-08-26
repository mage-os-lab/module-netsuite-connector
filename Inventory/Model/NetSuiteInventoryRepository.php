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

namespace MageOS\NetSuiteConnector\Inventory\Model;

use \MageOS\NetSuiteConnector\Inventory\Model\NetSuiteInventoryRepository\StockDataTransformationInterface;

/**
 * This class is responsible for stock data import from NS. Main method is processInventoryUpdates. It performs search
 * requests to NS, process responses and collect retrieved stock data.
 */
class NetSuiteInventoryRepository
{
    //phpcs:disable
    private \MageOS\NetSuiteConnector\Inventory\Model\Config\StockConfig $stockConfig;
    private \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement;
    private \MageOS\NetSuiteConnector\Inventory\Model\MagentoInventoryRepositoryInterface $magentoInventoryRepository;
    private \MageOS\NetSuiteConnector\Inventory\Model\NetSuiteInventoryRepository\StockDataTransformationInterface $dataTransformation;
    private \MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger;
    /**
     * @var Product\LastNetSuiteStockUpdate
     */
    private Product\LastNetSuiteStockUpdate $lastNetSuiteStockUpdate;

    public function __construct(
        \MageOS\NetSuiteConnector\Inventory\Model\Config\StockConfig $stockConfig,
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement,
        \MageOS\NetSuiteConnector\Inventory\Model\MagentoInventoryRepositoryInterface $magentoInventoryRepository,
        \MageOS\NetSuiteConnector\Inventory\Model\Product\LastNetSuiteStockUpdate $lastNetSuiteStockUpdate,
        \MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger,
        \MageOS\NetSuiteConnector\Inventory\Model\NetSuiteInventoryRepository\StockDataTransformationInterface $dataTransformation
    ) {
    //phpcs:enable
        $this->stockConfig = $stockConfig;
        $this->serviceManagement = $serviceManagement;
        $this->magentoInventoryRepository = $magentoInventoryRepository;
        $this->dataTransformation = $dataTransformation;
        $this->logger = $logger;
        $this->lastNetSuiteStockUpdate = $lastNetSuiteStockUpdate;
    }

    /**
     * Run stock import
     *
     * Retrieve stock data from NS, collect stock data and update products as batch
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processInventoryUpdates()
    {
        $savedSearchIds = $this->stockConfig->getCustomSearchId();
        $pageSize = $this->stockConfig->getCustomSearchPageSize();
        $netsuiteService = $this->serviceManagement->get();
        $netsuiteService->setSearchPreferences(true, $pageSize);

        $savedSearchParts = explode(',', $savedSearchIds);
        $stockData = [];
        $productIdsToReindex = [];
        foreach ($savedSearchParts as $savedSearchId) {
            $this->logger->debug('Processing SavedSearch ID: ' . $savedSearchId);
            list($stock, $productsReindex) = $this->dataTransformation->processSavedSearch(
                $netsuiteService,
                $savedSearchId
            );
            // phpcs:disable
            $stockData = array_replace($stockData, $stock); // We want to keep the keys (skus) in the array
            $productIdsToReindex = array_merge($productIdsToReindex, $productsReindex);
            // phpcs:enable
        }
        $this->logger->debug('Product IDs that were updated: ' . implode(',', $productIdsToReindex));
        //phpcs:ignore
        $this->logger->debug(print_r($stockData, true));

        $this->magentoInventoryRepository->saveInventoryData($stockData, $productIdsToReindex);
        $this->lastNetSuiteStockUpdate->execute($productIdsToReindex);
    }
}
