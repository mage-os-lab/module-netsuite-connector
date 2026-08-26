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


namespace MageOS\NetSuiteConnector\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Model\Entity\Attribute\Backend\Datetime;

/**
 * Class AddNetsuiteInventoryAttributePatch adds new product attribute
 * to set the last stock update date.
 */
class AddNetsuiteStockUpdateAttributePatch implements DataPatchInterface
{
    private \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup;
    private \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory;

    public function __construct(
        \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup,
        \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(): void
    {
        $this->moduleDataSetup->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->addAttribute(
            Product::ENTITY,
            'last_netsuite_stock_update',
            [
                'type' => 'datetime',
                'label' => 'Last NetSuite stock update',
                'input' => 'text',
                'required' => false,
                'sort_order' => 400,
                'global' => Attribute::SCOPE_GLOBAL,
                'group' => 'Product Details',
                'used_in_product_listing' => true,
                'visible_on_front' => true,
                'backend' => Datetime::class,
            ]
        );

        $this->moduleDataSetup->endSetup();
    }

    /**
     * {@inheritDoc}
     */
    public static function getDependencies(): array
    {
        return [
            \Magento\Catalog\Setup\Patch\Data\UpdateProductAttributes::class
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getAliases(): array
    {
        return [];
    }
}
