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
 *
 */

namespace MageOS\NetSuiteConnector\CustomerImport\Test\Integration\Model\ConfigProvider;

use Magento\TestFramework\Helper\Bootstrap;
use MageOS\NetSuiteConnector\CustomerImport\Model\ConfigProvider\Permissions;

// @codingStandardsIgnoreStart
//@SuppressWarnings(PHPMD)
class PermissionsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    protected $objectManager;

    protected function setUp():void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->objectManager = $objectManager;

        parent::setUp();
    }

    /**
     * @magentoConfigFixture default/mageos_netsuite/general/enabled 1
     * @magentoDbIsolation enabled
     * @magentoAppArea adminhtml
     *
     * Testing that exception is thrown if feature code is not recognized
     */
    public function testIsFeatureEnabledUnknownFeatureCode()
    {
        /** @var \MageOS\NetSuiteConnector\CustomerImport\Model\ConfigProvider\Permissions $permissions */
        $permissions = $this->objectManager->create(\MageOS\NetSuiteConnector\CustomerImport\Model\ConfigProvider\Permissions::class);

        $this->expectException(\MageOS\NetSuiteConnector\Core\Exception\ConfigurationException::class);
        $permissions->isFeatureEnabled('');
    }

    /**
     * @magentoConfigFixture default/mageos_netsuite/general/enabled 1
     * @magentoConfigFixture default/mageos_netsuite/enable_disable_features/import_customers 0
     * @magentoDbIsolation enabled
     * @magentoAppArea adminhtml
     *
     * Testing that Feature disabled returns correctly
     */
    public function testIsFeatureEnabledFeatureDisabled()
    {
        /** @var \MageOS\NetSuiteConnector\CustomerImport\Model\ConfigProvider\Permissions $permissions */
        $permissions = $this->objectManager->create(\MageOS\NetSuiteConnector\CustomerImport\Model\ConfigProvider\Permissions::class);

        $this->assertFalse($permissions->isFeatureEnabled(Permissions::IMPORT_CUSTOMER));
    }

    /**
     * @magentoConfigFixture default/mageos_netsuite/general/enabled 1s
     * @magentoConfigFixture default/mageos_netsuite/enable_disable_features/import_customers 1
     * @magentoDbIsolation enabled
     * @magentoAppArea adminhtml
     *
     * Testing that Feature enabled returns correctly
     */
    public function testIsFeatureEnabledFeatureEnabled()
    {
        /** @var \MageOS\NetSuiteConnector\CustomerImport\Model\ConfigProvider\Permissions $permissions */
        $permissions = $this->objectManager->create(\MageOS\NetSuiteConnector\CustomerImport\Model\ConfigProvider\Permissions::class);

        $this->assertTrue($permissions->isFeatureEnabled(Permissions::IMPORT_CUSTOMER));
    }
}
