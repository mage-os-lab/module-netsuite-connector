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

namespace MageOS\NetSuiteConnector\Core\Test\Integration\Model\Message;

use Magento\Framework\App\ObjectManager;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\LastUpdateManager;

class ExportNotRunTest extends \PHPUnit\Framework\TestCase
{

    public function setUp(): void
    {
        /** @var \MageOS\NetSuiteConnector\Core\Model\Config\DeveloperConfig $developerConfig */
        $developerConfig = ObjectManager::getInstance()->get(\MageOS\NetSuiteConnector\Core\Model\Config\DeveloperConfig::class);
        $developerConfig->setCacheEnabled(false);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppArea adminhtml
     */
    public function testIsDisplayedNoFlag()
    {
        $objectManager = ObjectManager::getInstance();
        /** @var \Magento\Framework\FlagManager $flagManager */
        $flagManager = $objectManager->create(\Magento\Framework\FlagManager::class);
        $flagManager->deleteFlag(LastUpdateManager::EXPORT_FLAG);

        /** @var \MageOS\NetSuiteConnector\Core\Model\Message\ExportNotRun $exportNotRun */
        $exportNotRun = $objectManager->create(\MageOS\NetSuiteConnector\Core\Model\Message\ExportNotRun::class);

        $this->assertTrue($exportNotRun->isDisplayed());
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppArea adminhtml
     * @magentoConfigFixture default/mageos_netsuite/developer/warn_if_export_not_run_after 0
     */
    public function testIsDisplayedWithWarnSetToZero()
    {
        $objectManager = ObjectManager::getInstance();
        /** @var \Magento\Framework\FlagManager $flagManager */
        $flagManager = $objectManager->create(\Magento\Framework\FlagManager::class);
        /**
         * Test data format: "Y-m-d H:i:s"
         */
        $flagManager->saveFlag(LastUpdateManager::EXPORT_FLAG, date("Y-m-d H:i:s", time()));

        /** @var \MageOS\NetSuiteConnector\Core\Model\Message\ExportNotRun $exportNotRun */
        $exportNotRun = $objectManager->create(\MageOS\NetSuiteConnector\Core\Model\Message\ExportNotRun::class);

        $this->assertFalse($exportNotRun->isDisplayed());
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppArea adminhtml
     * @magentoConfigFixture default/mageos_netsuite/developer/warn_if_export_not_run_after 0
     */
    public function testIsDisplayedWithWarnSetToZeroAndTimestampInPast()
    {
        $objectManager = ObjectManager::getInstance();
        /** @var \Magento\Framework\FlagManager $flagManager */
        $flagManager = $objectManager->create(\Magento\Framework\FlagManager::class);
        /**
         * Test data format: "Y-m-d H:i:s"
         */
        $flagManager->saveFlag(LastUpdateManager::EXPORT_FLAG, date("Y-m-d H:i:s", time() - 100));

        /** @var \MageOS\NetSuiteConnector\Core\Model\Message\ExportNotRun $exportNotRun */
        $exportNotRun = $objectManager->create(\MageOS\NetSuiteConnector\Core\Model\Message\ExportNotRun::class);

        $this->assertFalse($exportNotRun->isDisplayed());
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppArea adminhtml
     * @magentoConfigFixture default/mageos_netsuite/developer/warn_if_export_not_run_after 1
     */
    public function testIsDisplayedWithWarnSetToNonZeroAndTimestampInPast()
    {
        $objectManager = ObjectManager::getInstance();
        /** @var \Magento\Framework\FlagManager $flagManager */
        $flagManager = $objectManager->create(\Magento\Framework\FlagManager::class);
        /**
         * Test data format: "Y-m-d H:i:s"
         */
        $flagManager->saveFlag(LastUpdateManager::EXPORT_FLAG, date("Y-m-d H:i:s", time() - 100));

        /** @var \MageOS\NetSuiteConnector\Core\Model\Message\ExportNotRun $exportNotRun */
        $exportNotRun = $objectManager->create(\MageOS\NetSuiteConnector\Core\Model\Message\ExportNotRun::class);

        $this->assertFalse($exportNotRun->isDisplayed());
    }
}
