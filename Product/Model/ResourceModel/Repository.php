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
namespace MageOS\NetSuiteConnector\Product\Model\ResourceModel;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\Product;
use MageOS\NetSuiteConnector\Core\Model\MagentoTables;

/**
 * phpMd is complaining about too big of coupling but most of it comes from class constants being called.
 * Ignoring it.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Repository
{
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var \Magento\Eav\Model\Entity\Attribute
     */
    private $attributeModel;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var \MageOS\NetSuiteConnector\Core\Helper\EavHelper
     */
    private $eavHelper;

    public function __construct(
        \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Eav\Model\Entity\Attribute $attributeModel,
        \MageOS\NetSuiteConnector\Core\Helper\EavHelper $eavHelper
    ) {
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->attributeModel = $attributeModel;
        $this->resourceConnection = $resourceConnection;
        $this->eavHelper = $eavHelper;
        $this->productRepository = $productRepository;
    }

    public function loadProductsByNetSuiteId($inventoryItemIds): ?array
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteriaBuilder->addFilter('netsuite_internal_id', $inventoryItemIds, 'in');
        $searchCriteria = $searchCriteriaBuilder->create();

        return $this->productRepository->getList($searchCriteria)->getItems();
    }

    public function getOptionLabelByOptionId($attributeCode, $optionId): ?string
    {
        $attribute = $this->attributeModel->loadByCode(Product::ENTITY, $attributeCode);

        $optionLabel = $attribute->getSource()->getOptionText($optionId);
        if ($optionLabel === false) {
            return null;
        }

        return $optionLabel;
    }

    public function isEqual($recordRef, \Magento\Sales\Model\Order\Item $magentoOrderItem)
    {
        $product = $magentoOrderItem->getProduct();
        if (!is_object($product) || !$product->getId()) {
            return $recordRef->name === $magentoOrderItem->getSku();
        }

        if (!$recordRef || !$recordRef->internalId) {
            return false;
        }

        $netSuiteInternalId = $recordRef->internalId;
        if ($product->getNetsuiteInternalId() === $netSuiteInternalId) {
            return true;
        }

        if ($magentoOrderItem->getProductType() === \Magento\Bundle\Model\Product\Type::TYPE_CODE) {
            if ($this->eavHelper->mapBundleOptionToInternalId($magentoOrderItem) === $netSuiteInternalId) {
                return true;
            }
        }

        return false;
    }

    public function countProductsByNetSuiteIds($inventoryItemIds): array
    {
        $attributeId = $this->getNetSuiteInternalIdAttributeId();

        $connection = $this->resourceConnection->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $select = $connection->select()->from(
            ['attr' => $connection->getTableName(MagentoTables::PRODUCT_ENTITY_INT)],
            ['attr.value']
        )->where('attr.attribute_id=?', $attributeId)
            ->where(
                is_array($inventoryItemIds) ? 'attr.value IN (?)' : 'attr.value=?',
                $inventoryItemIds
            );

        $stmt = $connection->query($select);
        $rows = [];
        while ($row = $stmt->fetch()) {
            $rows[$row['value']] = 1;
        }

        return $rows;
    }

    public function mapNetSuiteIdsToProductIds($inventoryItemIds, $idToReturn = 'entity_id', $clearance = false): array
    {
        $attributeId = $this->getNetSuiteInternalIdAttributeId();

        $fieldName = 'entity_id';
        // phpcs:ignore
        if (class_exists('\Magento\Enterprise\Model\ProductMetadata')) {
            $fieldName = 'row_id';
        }

        $connection = $this->resourceConnection->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $select = $connection->select()->from(
            ['attr' => $connection->getTableName(MagentoTables::PRODUCT_ENTITY_INT)],
            'value'
        )->joinLeft(
            ['p' => $connection->getTableName(MagentoTables::PRODUCT_ENTITY)],
            'p.' . $fieldName . ' = attr.' . $fieldName,
            [$idToReturn, 'type_id', 'sku']
        )
        ->where('attr.attribute_id=?', $attributeId)
        ->where(
            is_array($inventoryItemIds) ? 'attr.value IN (?)' : 'attr.value=?',
            $inventoryItemIds
        );

        $stmt = $connection->query($select);
        $rows = [];
        while ($row = $stmt->fetch()) {
            $r = [
                'type_id' => $row['type_id'],
                'sku' => $row['sku']
            ];
            $r[$idToReturn] = $row[$idToReturn];

            $rows[$row['value']] = $r;
        }

        return $rows;
    }

    /**
     * @param $ids
     * @param string $idToReturn
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function mapProductIdsToNetSuiteIds($ids, $idToReturn = 'entity_id')
    {
        $attributeId = $this->getNetSuiteInternalIdAttributeId();

        $fieldName = 'entity_id';
        // phpcs:ignore
        if (class_exists('\Magento\Enterprise\Model\ProductMetadata')) {
            $fieldName = 'row_id';
        }

        $connection = $this->resourceConnection->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $select = $connection->select()->from(
            ['attr' => $connection->getTableName(MagentoTables::PRODUCT_ENTITY_INT)],
            'value'
        )->joinLeft(
            ['p' => $connection->getTableName(MagentoTables::PRODUCT_ENTITY)],
            'p.' . $fieldName . ' = attr.' . $fieldName,
            [$idToReturn]
        )->where('attr.attribute_id=?', $attributeId)
        ->where('p.entity_id IN (?)', $ids);

        return $connection->fetchPairs($select);
    }

    /**
     * @return \Magento\Framework\EntityManager\MetadataPool
     */
    private function getMetadataPool()
    {
        return \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\EntityManager\MetadataPool::class);
    }

    public function fetchProductDataForPrefetch($inventoryItemIds): array
    {
        static $nsIdAttributeId = null, $nsIdTable = null,
        $nameAttributeId = null, $nameAttributeTable,
        $priceAttributeId = null, $priceAttributeTable,
        $linkField;

        if (!$nsIdAttributeId) {
            $attribute = $this->attributeModel->loadByCode(Product::ENTITY, 'netsuite_internal_id');
            $nsIdAttributeId = $attribute->getAttributeId();
            $nsIdTable = $attribute->getBackend()->getTable();

            $attribute = $this->attributeModel->loadByCode(Product::ENTITY, 'price');
            $priceAttributeId = $attribute->getAttributeId();
            $priceAttributeTable = $attribute->getBackend()->getTable();

            $attribute = $this->attributeModel->loadByCode(Product::ENTITY, 'name');
            $nameAttributeId = $attribute->getAttributeId();
            $nameAttributeTable = $attribute->getBackend()->getTable();

            $linkField = $this->getMetadataPool()->getMetadata(ProductInterface::class)->getLinkField();
        }

        $connection = $this->resourceConnection->getConnection(ResourceConnection::DEFAULT_CONNECTION);

        $productTable = $connection->getTableName(MagentoTables::PRODUCT_ENTITY);

        $select = $connection->select()
            ->from(
                ['attr_ns_id' => $nsIdTable],
                ['attr_ns_id.value AS netsuite_internal_id']
            )
            ->join(
                ['attr_price' => $priceAttributeTable],
                $connection->quoteInto(
                    'attr_price.' . $linkField . ' = attr_ns_id.' . $linkField . ' AND attr_price.attribute_id=?',
                    $priceAttributeId
                ),
                ['attr_price.value AS price']
            )
            ->join(
                ['attr_name' => $nameAttributeTable],
                $connection->quoteInto(
                    'attr_name.' . $linkField . ' = attr_ns_id.' . $linkField . ' AND attr_name.attribute_id=?',
                    $nameAttributeId
                ),
                ['attr_name.value AS name']
            )
            ->join(
                ['p' => $connection->getTableName($productTable)],
                'p.' . $linkField . ' = attr_ns_id.' . $linkField . '',
                ['sku']
            )
            ->where(
                'attr_ns_id.attribute_id=?',
                $nsIdAttributeId
            )
            ->where(
                \is_array($inventoryItemIds) ? 'attr_ns_id.value IN (?)' : 'attr_ns_id.value=?',
                $inventoryItemIds
            );

        return $connection->fetchAssoc($select);
    }

    protected function getNetSuiteInternalIdAttributeId(): int
    {
        static $attributeId = null;
        if ($attributeId === null) {
            $attribute = $this->attributeModel->loadByCode(Product::ENTITY, 'netsuite_internal_id');
            $attributeId = $attribute->getAttributeId();
        }

        return (int)$attributeId;
    }
}
