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

namespace MageOS\NetSuiteConnector\Product\Model\Command;

use NetSuite\Classes\ItemSearch;
use NetSuite\Classes\ItemSearchBasic;
use NetSuite\Classes\SearchCustomFieldList;
use NetSuite\Classes\SearchRequest;
use NetSuite\Classes\SearchStringCustomField;
use NetSuite\Classes\SearchStringField;
use NetSuite\Classes\SearchStringFieldOperator;

/**
 * Assigns netsuite_internal_id to magento products
 * Process uses direct SQL queries and batching to make it work faster with large products amounts
 * @TODO think about NS batching queries
 */
class RelinkProcessor
{
    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management
     */
    protected $serviceManagement;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $connection;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Action
     */
    private $productAction;
    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\Logger\Logger
     */
    private $logger;
    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @param \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement
     * @param \Magento\Framework\App\ResourceConnection $connection
     * @param \Magento\Catalog\Model\ResourceModel\Product\Action $productAction
     * @param \MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement,
        \Magento\Framework\App\ResourceConnection $connection,
        \Magento\Catalog\Model\ResourceModel\Product\Action $productAction,
        \MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata
    ) {
        $this->serviceManagement = $serviceManagement;
        $this->connection = $connection;
        $this->productAction = $productAction;
        $this->logger = $logger;
        $this->productMetadata = $productMetadata;
    }

    /**
     * Method handle relink process for products
     * @param bool $isDryRun
     * @param bool $isSKUCustomField
     * @param string $netsuiteIdSearchField
     * @param int $batchSize
     * @param int $startId
     * @throws \Zend_Db_Statement_Exception
     */
    public function process(
        bool $isDryRun,
        bool $isSKUCustomField,
        string $netsuiteIdSearchField,
        int $batchSize,
        int $startId
    ): void {
        $totalProduct = $this->getTotalProducts($startId);
        $this->logger->addInfo(sprintf('Total products found in Database : %s ', $totalProduct['amount']));
        $batchAmount = (int)ceil($totalProduct['amount'] / $batchSize);
        $this->logger->addInfo(sprintf('Total Batches to work : %s ', $batchAmount));
        $this->logger->addInfo('~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~');
        for ($i = 0; $i <= $batchAmount; $i++) {
            $this->logger->addInfo(sprintf('Batch number Processing : %s ', $i));
            $this->logger->addInfo('~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~');
            $offset = $i * $batchSize;
            $products = $this->getProductData($startId, $offset, $batchSize);
            foreach ($products as $product) {
                try {
                    $this->logger->addInfo('~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~');
                    $this->logger->addInfo(sprintf('Linking product ID : %s ', $product['entity_id']));
                    $netsuiteItem = $this->getNetSuiteItemBySku(
                        $product['sku'],
                        $netsuiteIdSearchField,
                        $isSKUCustomField
                    );
                    if ($netsuiteItem !== null) {
                        $this->processInternalIdChange($product, $netsuiteItem, $isDryRun);
                    }
                } catch (\Exception $e) {
                    $this->logger->addError("Exception during linking product with ID" .
                        " {$product['entity_id']}. Error: {$e->getMessage()}");
                    $this->logger->addInfo('~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~');
                }
            }
        }
    }

    /**
     * @param int $startId
     * @param int $offset
     * @param int $limit
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    private function getProductData(int $startId, int $offset, int $limit): array
    {
        $version = $this->productMetadata->getEdition();
        $idField = 'entity_id';
        if ($version === "B2B") {
            $idField = 'row_id';
        }
        // phpcs:disable
        return $this->connection->getConnection()
            ->query("SELECT cpe.{$idField} as entity_id, cpe.sku as sku, cpei.`value` as netsuite_internal_id " .
                "FROM catalog_product_entity as cpe LEFT JOIN catalog_product_entity_int as cpei " .
                "on cpe.{$idField} = cpei.{$idField} AND cpei.`attribute_id` = " .
                "(SELECT attribute_id FROM eav_attribute WHERE eav_attribute.`attribute_code`='netsuite_internal_id' " .
                "AND eav_attribute.`entity_type_id`=4) where cpe.`entity_id`> " .
                $startId . ' limit ' . $limit . ' offset ' . $offset)->fetchAll();
        // phpcs:enable
    }

    /**
     * return general amount of products
     * @param int $startId
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    private function getTotalProducts(int $startId): array
    {
        // phpcs:disable
        return $this->connection->getConnection()
            ->query('Select count(*) as amount from catalog_product_entity where entity_id >=' . $startId)->fetch();
        // phpcs:enable
    }

    /**
     * Fetch NetSuite Item based on $netsuiteIdSearchField and return the record line
     *
     * @param string $sku
     * @param $netsuiteIdSearchField
     * @param $isSKUCustomField
     * @return \NetSuite\Classes\Record|null |null
     */
    protected function getNetSuiteItemBySku(
        string $sku,
        string $netsuiteIdSearchField,
        bool $isSKUCustomField
    ) {
        $searchRequest = null;
        if ($isSKUCustomField) {
            $searchRequest = $this->getSearchCustomField($sku, $netsuiteIdSearchField);
        }
        if (null === $searchRequest) {
            $searchRequest = $this->getSearchRequestBasic($sku, $netsuiteIdSearchField);
        }

        $response = $this->serviceManagement->get()->search($searchRequest);
        if (!$response) {
            $this->logger->addError("Search for " . $sku . " failed: No Response from NetSuite");
            return null;
        }
        if (!$response->searchResult->status->isSuccess) {
            $this->logger->addInfo(
                "Search for " . $sku . " failed: " .
                json_encode($response->searchResult->status->statusDetail) ?? ' No details.'
            );
            return null;
        }
        if (!$response->searchResult->totalRecords) {
            $this->logger->addError("Product with SKU : " . $sku . ' not found in NetSuite');
            return null;
        }

        return $response->searchResult->recordList->record[0];
    }

    /**
     * @param string $sku
     * @param string $netsuiteIdSearchField
     * @return SearchRequest
     */
    private function getSearchRequestBasic(string $sku, string $netsuiteIdSearchField): SearchRequest
    {
        $netsuiteSearch = new ItemSearchBasic();
        $netsuiteSearch->{$netsuiteIdSearchField} = new SearchStringField();
        $netsuiteSearch->{$netsuiteIdSearchField}->searchValue = $sku;
        $netsuiteSearch->{$netsuiteIdSearchField}->operator = SearchStringFieldOperator::is;

        $searchRequest = new SearchRequest();
        $searchRequest->searchRecord = $netsuiteSearch;
        return $searchRequest;
    }

    /**
     * @param string $sku
     * @param string $netsuiteIdSearchField
     * @return SearchRequest
     */
    private function getSearchCustomField(string $sku, string $netsuiteIdSearchField): SearchRequest
    {
        $netsuiteSearch = new ItemSearch();
        $netsuiteSearchBasic = new ItemSearchBasic();

        $netsuiteSearchCustomField = new SearchStringCustomField();
        $netsuiteSearchCustomField->scriptId = $netsuiteIdSearchField;
        $netsuiteSearchCustomField->operator = SearchStringFieldOperator::is;
        $netsuiteSearchCustomField->searchValue = $sku;

        $netsuiteSearchCustomFieldList = new SearchCustomFieldList();
        $netsuiteSearchCustomFieldList->customField = $netsuiteSearchCustomField;

        $netsuiteSearchBasic->customFieldList = $netsuiteSearchCustomFieldList;
        $netsuiteSearch->basic = $netsuiteSearchBasic;

        $searchRequest = new SearchRequest();
        $searchRequest->searchRecord = $netsuiteSearch;
        return $searchRequest;
    }

    /**
     * @param array $magentoProduct
     * @param $netsuiteItem
     * @param $isDryRun
     * @throws \Exception
     */
    private function processInternalIdChange(array $magentoProduct, $netsuiteItem, $isDryRun)
    {
        $message = sprintf(
            'Product ID %s: no change of internal id (%s)',
            $magentoProduct['sku'],
            $netsuiteItem->internalId
        );
        if ($magentoProduct['netsuite_internal_id'] != $netsuiteItem->internalId) {
            $message = sprintf(
                'Product ID %s (SKU : %s) linked with Netsuite Item (Id : %s)',
                $magentoProduct['entity_id'],
                $magentoProduct['sku'],
                $netsuiteItem->internalId
            );

            if (!$isDryRun) {
                $this->changeInternalId($magentoProduct['entity_id'], $netsuiteItem->internalId);
            }
        }
        $this->logger->addInfo($message);
    }

    /**
     * @param $productEntityId
     * @param $newInternalId
     * @throws \Exception
     */
    private function changeInternalId($productEntityId, $newInternalId)
    {
        $this->productAction->updateAttributes([$productEntityId], ['netsuite_internal_id' => $newInternalId], '');
    }
}
