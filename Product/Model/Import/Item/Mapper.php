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

namespace MageOS\NetSuiteConnector\Product\Model\Import\Item;

use NetSuite\Classes\ItemMatrixType;
use NetSuite\Classes\KitItem;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\CustomFieldAccess;

class Mapper
{
    /**
     * @var array
     */
    private $productMappers;

    public function __construct(
        array $productMappers
    ) {
        $this->productMappers = $productMappers;
    }

    /**
     * Adding support for additional product Mappers. After full refactoring, following mappers should exists:
     * - Inventory Item - default
     * - NonInventory Item - default
     * - Assembly Item - default
     * - Kit Item - _kitItem
     * - Matrix Item - _matrixItem
     * - Item Group (experimental only) - _itemGroup
     *
     * TODO: Create separate Mappers!
     *
     * @param \NetSuite\Classes\InventoryItem $inventoryItem
     * @return \MageOS\NetSuiteConnector\Product\Model\Mapper\Product
     */
    public function getInstance($inventoryItem)
    {
        if ($inventoryItem instanceof \NetSuite\Classes\ItemGroup) {
            return $this->productMappers['_itemGroup'];
        }

        if ($inventoryItem instanceof KitItem) {
            return $this->productMappers['_kitItem'];
        }

        $matrixChildren = CustomFieldAccess::get($inventoryItem, 'custitem_magento_matrix_children');
        if ($matrixChildren && $inventoryItem->matrixType !== ItemMatrixType::_child) {
            return $this->productMappers['_matrixItem'];
        }

        /**
         * For AssemblyItem and NonInventoryItem we are using "default" mapper
         */

        return $this->productMappers['default'];
    }
}
