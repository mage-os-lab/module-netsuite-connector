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
namespace MageOS\NetSuiteConnector\Product\Model\Product\Import;

use Magento\Framework\DB\Adapter\AdapterInterface;
use MageOS\NetSuiteConnector\Core\Model\MagentoTables;
use Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator;
use Magento\Store\Model\ScopeInterface;

class UrlCollisionValidator
{
    public function __construct(
        private \Magento\Framework\App\ResourceConnection $resourceConnection,
        private \Magento\Store\Model\StoreManagerInterface $storeManager,
        private \Magento\Catalog\Model\Product\Url $productUrl,
        private \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        private \MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger,
        private array $storeCollection = [],
        private ?\Magento\Framework\DB\Adapter\AdapterInterface $connection = null
    ) {
        $this->initStores();
    }

    public function validate(array $rows): array
    {
        // Get rewrites which already exists
        $rewrites = $this->getExistingRewrites($rows);

        foreach ($rows as $index => $row) {
            $sku = $row['sku'] ?: null;
            $storeCode = $row['store_view_code'] ?? $this->storeManager->getDefaultStoreView()->getCode();
            if ($sku === null) {
                continue;
            }
            $storeId = $this->getStoreIdByStoreCode($storeCode);
            $productUrlSuffix = $this->getProductUrlSuffix($storeId);

            // set exist key and go to next iteration
            // we override it when 'url_key' index exists
            if ($rewrites && !isset($row['url_key'])) {
                $existRewrite = $this->getExistRewrite($rewrites, $row['sku'], $storeId);
                if ($existRewrite) {
                    $existsKey = str_replace($productUrlSuffix, '', $existRewrite['request_path']);
                    $existsKey = trim($existsKey);
                    $rows[$index]['url_key'] = $existsKey;
                    continue;
                }
            }

            $urlKey = $this->getUrlKey($row);
            $urlKey = $urlKey . $productUrlSuffix;
            // set url key to product
            $rows[$index]['url_key'] = $urlKey;

            // look for rewrite duplicates w/o current product
            $duplicates = $this->getDuplicates($sku, $urlKey, $storeId);
            if (!$duplicates) {
                continue;
            }

            // Generate and check for duplicates new url key
            $urlKey = $this->getNewUrlKey($urlKey, $storeId);
            $rows[$index]['url_key'] = $urlKey;
            $this->logger->debug(
                sprintf(
                    'Updated url rewrite for sku #%s and store code - %s',
                    $sku,
                    $storeCode
                )
            );
        }

        return $rows;
    }

    private function getExistRewrite(array $rewrites, string $sku, int $storeId): array
    {
        $existRewrite = [];
        foreach ($rewrites as $rewrite) {
            if ($rewrite['sku'] === $sku && $rewrite['store_id'] == $storeId && !empty($rewrite['request_path'])) {
                $existRewrite = $rewrite;
                break;
            }
        }
        return $existRewrite;
    }

    private function getNewUrlKey(string $requestPath, int $storeId): string
    {
        $i = 1;

        do {
            $newUrlKey = $requestPath . '-' . $i;
            $canUseNewUrlKey = $this->isRewriteExists($newUrlKey, $storeId);
            $i++;
        } while ($canUseNewUrlKey);

        return $newUrlKey;
    }

    private function getExistingRewrites(array $rows): array
    {
        $skuList = array_column($rows, 'sku');
        $connection = $this->getConnection();
        return $connection->fetchAll(
            $connection->select()->from(
                ['cpe' => $this->resourceConnection->getTableName(MagentoTables::PRODUCT_ENTITY)],
                ['sku']
            )->joinLeft(
                ['ur' => $this->resourceConnection->getTableName(MagentoTables::URL_REWRITE)],
                "cpe.entity_id = ur.entity_id AND ur.entity_type='product'",
                ['request_path', 'store_id']
            )
                ->where('sku IN (?)', $skuList)
                ->where('target_path LIKE \'catalog/product/view/id/%\'')
        );
    }

    private function isRewriteExists(string $newUrlKey, int $storeId): bool
    {
        $connection = $this->getConnection();
        return (bool)$connection->fetchOne(
            $connection->select()->from(
                ['ur' => $this->resourceConnection->getTableName(MagentoTables::URL_REWRITE)],
                ['COUNT(*)']
            )
                ->where('ur.request_path = (?)', $newUrlKey)
                ->where('ur.store_id = (?)', $storeId)
        );
    }

    private function getDuplicates(string $sku, string $urlKey, int $storeId): int
    {
        $connection = $this->getConnection();
        $joinCondition = sprintf('cpe.entity_id = ur.entity_id AND cpe.sku != \'%s\'', $sku);
        return (int)$connection->fetchOne(
            $connection->select()->from(
                ['ur' => $this->resourceConnection->getTableName(MagentoTables::URL_REWRITE)],
                ['COUNT(*)']
            )->joinLeft(
                ['cpe' => $this->resourceConnection->getTableName(MagentoTables::PRODUCT_ENTITY)],
                $joinCondition,
                ['cpe.entity_id']
            )->where('ur.request_path = (?)', $urlKey)
                ->where('ur.store_id = (?)', $storeId)
        );
    }

    private function getStoreIdByStoreCode(string $storeCode): int
    {
        $storeId = 0;
        foreach ($this->getStores() as $store) {
            if ($store->getCode() === $storeCode) {
                $storeId = (int)$store->getId();
                break;
            }
        }

        return $storeId;
    }

    protected function getProductUrlSuffix(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            ProductUrlPathGenerator::XML_PATH_PRODUCT_URL_SUFFIX,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    private function getStores(): array
    {
        return $this->storeCollection;
    }

    private function getUrlKey(array $row): string
    {
        return $row['url_key'] ?? $this->productUrl->formatUrlKey($row['sku']);
    }

    private function initStores(): void
    {
        $this->storeCollection = $this->storeManager->getStores();
    }

    private function getConnection(): AdapterInterface
    {
        if (!$this->connection) {
            $this->connection = $this->resourceConnection->getConnection();
        }
        return $this->connection;
    }
}
