<?php
/**
 *  RocketWeb
 *
 *  NOTICE OF LICENSE
 *
 *  This source file is subject to the Open Software License (OSL 3.0)
 *  that is bundled with this package in the file LICENSE.txt.
 *  It is also available through the world-wide-web at this URL:
 *  http://opensource.org/licenses/osl-3.0.php
 *
 * @category  RocketWeb
 * @package   MageOS_NetSuiteConnector
 * @copyright Copyright (c) 2026 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    Rocket Web Inc.
 */
namespace MageOS\NetSuiteConnector\Setup\Patch\Data;

use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddAttributes implements DataPatchInterface
{
    private const ATTRIBUTE_CODE = 'netsuite_internal_id';

    public function __construct(
        private readonly \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup,
        private readonly \Magento\Customer\Setup\CustomerSetupFactory $customerSetupFactory,
    ) {
    }

    public function apply()
    {
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $attribute = $customerSetup->getAttribute(
            CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
            self::ATTRIBUTE_CODE
        );
        if (!$attribute) {
            $customerSetup->addAttribute(
                CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                self::ATTRIBUTE_CODE,
                [
                    'label' => 'NetSuite Internal Id',
                    'required' => 0,
                    'system' => 0,
                    'position' => 100,
                    'type' => 'int',
                ]
            );

            $customerSetup->getEavConfig() // @phpstan-ignore-line
                ->getAttribute(
                    CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                    self::ATTRIBUTE_CODE
                )
                ->setData('used_in_forms', ['adminhtml_customer'])
                ->save();
        }
        $addressAttribute = $customerSetup->getAttribute(
            AddressMetadataInterface::ENTITY_TYPE_ADDRESS,
            self::ATTRIBUTE_CODE
        );
        if (!$addressAttribute) {
            $customerSetup->addAttribute(
                AddressMetadataInterface::ENTITY_TYPE_ADDRESS,
                self::ATTRIBUTE_CODE,
                [
                    'label' => 'NetSuite Internal Id',
                    'required' => 0,
                    'system' => 0,
                    'position' => 100,
                    'type' => 'int',
                ]
            );
            $customerSetup->getEavConfig() // @phpstan-ignore-line
                ->getAttribute(
                    AddressMetadataInterface::ENTITY_TYPE_ADDRESS,
                    self::ATTRIBUTE_CODE
                )
                ->setData('used_in_forms', ['adminhtml_customer_address'])
                ->save();

        }
        return $this;
    }

    public function getAliases(): array
    {
        return [];
    }

    public static function getDependencies(): array
    {
        return [
            \Magento\Customer\Setup\Patch\Data\DefaultCustomerGroupsAndAttributes::class
        ];
    }
}
