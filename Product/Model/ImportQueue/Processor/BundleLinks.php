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
namespace MageOS\NetSuiteConnector\Product\Model\ImportQueue\Processor;

use Magento\Framework\App\ResourceConnection;
use MageOS\NetSuiteConnector\Core\Model\ImportQueue\EntityProcessor;
use MageOS\NetSuiteConnector\Core\Model\MagentoTables;

class BundleLinks extends EntityProcessor
{
    public function process(string $entity): array
    {
        $this->importBundleLinks($this->importRowList->getRawEntityData($entity));
        return [];
    }

    /**
     * @param array $rows
     */
    private function importBundleLinks(array $rows)
    {
        if (empty($rows)) {
            return;
        }

        $skus = [];
        foreach ($rows as $row) {
            $skus[] = $row['sku'];
        }

        $linkDatas = [];
        $entities = $this->mapSkusToEntityIds($skus);
        foreach ($rows as $row) {
            $entityId = $entities[$row['sku']];
            $linkData = [
                'product_id' => $entityId,
                'netsuite_internal_id' => $row['netsuite_internal_id'],
                'option_sku' => $row['option_sku'],
            ];

            $linkDatas[] = $linkData;
        }

        $connection = $this->resourceConnection->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $connection->insertOnDuplicate(
            $connection->getTableName('mageos_netsuite_bundle_product_link'),
            $linkDatas,
            ['product_id', 'netsuite_internal_id', 'option_sku']
        );
    }

    /**
     * @param $skus
     * @return array
     */
    public function mapSkusToEntityIds($skus)
    {
        $connection = $this->resourceConnection->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        return $connection->fetchPairs($connection->select()->from(
            ['cpe' => $connection->getTableName(MagentoTables::PRODUCT_ENTITY)],
            ['sku', 'entity_id']
        )->where('sku IN(?)', $skus));
    }
}
