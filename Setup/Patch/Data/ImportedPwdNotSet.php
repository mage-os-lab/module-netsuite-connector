<?php
/*
 *   RocketWeb
 *
 *  NOTICE OF LICENSE
 *
 *  This source file is subject to the Open Software License (OSL 3.0)
 *  that is bundled with this package in the file LICENSE.txt.
 *  It is also available through the world-wide-web at this URL:
 *  http://opensource.org/licenses/osl-3.0.php
 *
 *  @category  RocketWeb
 *  @package   MageOS_NetSuiteConnector
 *  @copyright Copyright (c) 2026 RocketWeb (http://rocketweb.com)
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  @author    Rocket Web Inc.
 *
 *
 */

declare(strict_types=1);

namespace MageOS\NetSuiteConnector\Setup\Patch\Data;

/**
 * Class ImportedPwdNotSet - added custom attribute to hold set password flag
 */
class ImportedPwdNotSet implements \Magento\Framework\Setup\Patch\DataPatchInterface
{
    private \Magento\Customer\Setup\CustomerSetupFactory $customerSetupFactory;
    private \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup;

    /**
     * ImportedPwdNotSet constructor.
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup
     * @param \Magento\Customer\Setup\CustomerSetupFactory $customerSetupFactory
     */
    public function __construct(
        \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup,
        \Magento\Customer\Setup\CustomerSetupFactory $customerSetupFactory
    ) {

        $this->customerSetupFactory = $customerSetupFactory;
        $this->moduleDataSetup = $moduleDataSetup;
    }
    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [
            \Magento\Customer\Setup\Patch\Data\DefaultCustomerGroupsAndAttributes::class,
            \MageOS\NetSuiteConnector\Setup\Patch\Data\AddAttributes::class
        ];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $customerSetup->addAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            'imported_pwd_not_set',
            [
                'type'         => 'int',
                'label'        => 'Imported, password not set',
                'input'        => 'text',
                'required'     => false,
                'visible'      => false,
                'user_defined' => false,
                'position'     => 150,
                'system'       => 0
            ]
        );

        $customerSetup->getEavConfig()->getAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            'imported_pwd_not_set'
        )->setData('used_in_forms', ['adminhtml_customer'])
            ->save();

        $this->moduleDataSetup->endSetup();
    }
}
