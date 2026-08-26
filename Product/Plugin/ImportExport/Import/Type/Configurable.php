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
 *
 */

namespace MageOS\NetSuiteConnector\Product\Plugin\ImportExport\Import\Type;

use Magento\ConfigurableImportExport\Model\Import\Product\Type\Configurable as CoreConfigurable;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * Class Configurable - plugin to extend Configurable product import behavior.
 * Makes possible to remove redundant simple products from existed configurable and clena up attributes that are not
 * used for combinations anymore.
 */
class Configurable
{
    /**
     * @var ResourceConnection
     */
    private $resource;
    /**
     * @var array
     */
    private $superAttributesData = [];
    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * Configurable constructor.
     * @param ResourceConnection $resource
     */
    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @param CoreConfigurable $subject
     * @param CoreConfigurable $result
     * @return CoreConfigurable
     */
    public function afterSaveData(CoreConfigurable $subject, CoreConfigurable $result)
    {
        $this->superAttributesData = $subject->getSuperAttributeData();
        if (null === $this->superAttributesData) {
            return $result;
        }
        $this->connection = $this->resource->getConnection();
        $this->removeAttributes();
        $this->removeLinks();
        return $result;
    }

    /**
     * Method check are there any redundant super attributes exist in configurable product after its update
     * If there are - method removes them.
     */
    private function removeAttributes()
    {
        $removeAttributes = [];
        $mainTable = $this->resource->getTableName('catalog_product_super_attribute');
        foreach ($this->superAttributesData['attributes'] as $productId => $attributesData) {
            foreach ($attributesData as $attrId => $row) {
                $row['product_id'] = $productId;
                $row['attribute_id'] = $attrId;
                $mainData[] = $row;
            }
            $removeAttributes[] = $this->connection->quoteInto('(product_id=?', $productId) . ' AND ' .
                $this->connection->quoteInto('attribute_id NOT IN(?))', array_keys($attributesData));
        }
        if ($removeAttributes) {
            $this->connection->delete($mainTable, implode(' OR ', $removeAttributes));
        }
    }

    /**
     * Method check are there any redundant super super links exist (parent-child) according to new information from
     * NetSuite
     * If there are - method removes them.
     */
    private function removeLinks()
    {
        $linkTable = $this->resource->getTableName('catalog_product_super_link');
        if ($this->superAttributesData['super_link']) {
            $superLinks = $this->superAttributesData['super_link'];

            $links = [];
            foreach ($superLinks as $entry) {
                $links[$entry['parent_id']][] = $entry['product_id'];
            }

            $toDelete = [];
            foreach ($links as $parent_id => $products) {
                $toDelete[] = $this->connection->quoteInto('(parent_id=?', $parent_id) . ' AND ' .
                    $this->connection->quoteInto('product_id NOT IN(?))', $products);
            }

            if ($toDelete) {
                $this->connection->delete($linkTable, implode(' OR ', $toDelete));
            }
        }
    }
}
