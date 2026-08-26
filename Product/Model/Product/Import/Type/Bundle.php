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

// @codingStandardsIgnoreFile
namespace MageOS\NetSuiteConnector\Product\Model\Product\Import\Type;

use Magento\CatalogImportExport\Model\Import\Product;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory;
use Magento\Framework\App\ResourceConnection;

/**
 * This class extends the core class so all warnings should be ignored.
 *
 * @SuppressWarnings(PHPMD)
 */
class Bundle extends \Magento\BundleImportExport\Model\Import\Product\Type\Bundle
{
    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\Plugin\ImportExport\PluginState
     */
    private $state;

    /**
     * BundleImportExport constructor.
     * @param \MageOS\NetSuiteConnector\Core\Model\Plugin\ImportExport\PluginState $state
     * @param CollectionFactory $attrSetColFac
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $prodAttrColFac
     * @param ResourceConnection $resource
     * @param array $params
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Core\Model\Plugin\ImportExport\PluginState $state,
        CollectionFactory $attrSetColFac,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $prodAttrColFac,
        ResourceConnection $resource,
        array $params
    ) {
        $this->state = $state;

        parent::__construct(
            $attrSetColFac,
            $prodAttrColFac,
            $resource,
            $params
        );
    }

    /**
     * @inheritdoc
     */
    public function saveData()
    {
        if (!$this->state->isRunning()) {
            return parent::saveData();
        }

        $newSku = $this->_entityModel->getNewSku();
        while ($bunch = $this->_entityModel->getNextBunch()) {
            foreach ($bunch as $rowNum => $rowData) {
                if (!$this->_entityModel->isRowAllowedToImport($rowData, $rowNum)) {
                    continue;
                }
                if (!isset($newSku[strtolower($rowData[Product::COL_SKU])])) {
                    continue;
                }
                $productData = $newSku[strtolower($rowData[Product::COL_SKU])];

                if ($this->_type != $productData['type_id']) {
                    continue;
                }
                $this->parseSelections($rowData, $productData[$this->getProductEntityLinkField()]);
            }
            if (!empty($this->_cachedOptions)) {
                $this->retrieveProductsByCachedSkus();
                $this->populateExistingOptions();
                $this->insertOptions();
                $this->insertSelections();
                $this->clear();
            }
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function populateSelectionTemplate($selection, $optionId, $parentId, $index)
    {
        if (!$this->state->isRunning()) {
            return parent::populateSelectionTemplate($selection, $optionId, $parentId, $index);
        }

        if (!isset($selection['parent_product_id'])) {
            if (!isset($this->_cachedSkuToProducts[$selection['sku']])) {
                return false;
            }
            $productId = $this->_cachedSkuToProducts[$selection['sku']];
        } else {
            $productId = $selection['product_id'];
        }
        $populatedSelection = [
            'selection_id' => null,
            'option_id' => (int)$optionId,
            'parent_product_id' => (int)$parentId,
            'product_id' => (int)$productId,
            'position' => (int)$index,
            'is_default' => (isset($selection['default']) && $selection['default']) ? 1 : 0,
            'selection_price_type' => (isset($selection['price_type']) && $selection['price_type'] == self::VALUE_FIXED)
                ? self::SELECTION_PRICE_TYPE_FIXED : self::SELECTION_PRICE_TYPE_PERCENT,
            'selection_price_value' => (isset($selection['price'])) ? (float)$selection['price'] : 0.0,
            'selection_qty' => (isset($selection['default_qty'])) ? (float)$selection['default_qty'] : 1.0,
            'selection_can_change_qty' => isset($selection['can_change_quantity']) ? (int)$selection['can_change_quantity'] : 1,
        ];

        if (isset($selection['selection_id'])) {
            $populatedSelection['selection_id'] = $selection['selection_id'];
        }

        return $populatedSelection;
    }
}
