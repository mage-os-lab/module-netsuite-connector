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

namespace MageOS\NetSuiteConnector\Product\Model\Prefetch;

use MageOS\NetSuiteConnector\Product\Model\Config\ProductConfig;
use MageOS\NetSuiteConnector\Core\Model\ImportQueueManager;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\CustomFieldAccess;

/**
 * Use this class to prefetch products stored as a list of NetSuite IDs in some custom field
 */
abstract class CustomFieldIdListPrefetcher implements PrefetchIdSourceInterface
{
    /**
     * @var array
     */
    protected $products = [];

    /**
     * @var ProductConfig
     */
    protected $config;

    /**
     * @var ImportQueueManager
     */
    private $importManager;

    /**
     * RelatedProducts constructor.
     * @param ProductConfig $config
     * @param ImportQueueManager $importManager
     */
    public function __construct(
        ProductConfig $config,
        ImportQueueManager $importManager
    ) {
        $this->config = $config;
        $this->importManager = $importManager;
    }

    /**
     * @param array $records
     * @return array
     * @throws \InvalidArgumentException
     */
    public function execute(array $records): array
    {
        $result = [];

        $productsField = $this->getCustomFieldName();

        foreach ($records as $record) {
            $productIds = CustomFieldAccess::getList(
                $record,
                $productsField
            );

            if (!$productIds) {
                continue;
            }

            $this->products[$record->internalId] = $productIds;

            // phpcs:ignore
            $result = array_merge(
                $result,
                $productIds
            );
        }

        return array_unique($result);
    }

    /**
     * Returns the name of a custom field which stores netsuite internal IDs list
     * @return string
     */
    abstract public function getCustomFieldName(): string;

    /**
     * @inheritdoc
     */
    public function cleanup()
    {
        $this->products = [];
    }

    /**
     * Returns related products SKUs for the specified internal ID
     * @param string $internalId
     * @return array
     */
    protected function getSkuListForProduct(string $internalId): array
    {
        $relatedProductIds = $this->products[$internalId] ?? null;

        if (!empty($relatedProductIds)) {
            try {
                $mapped = $this->importManager->mapIds($relatedProductIds);
            } catch (\Exception $e) {
                $mapped = [];
            }

            $skuList = [];
            foreach ($mapped as $data) {
                $skuList[] = $data['sku'];
            }

            if (!empty($skuList)) {
                return $skuList;
            }
        }

        return [];
    }
}
