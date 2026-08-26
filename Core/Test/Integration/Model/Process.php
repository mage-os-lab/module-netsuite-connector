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
 */

namespace MageOS\NetSuiteConnector\Core\Test\Integration\Model;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\MockObject\MockObject;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management as ServiceManagement;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\LastUpdateManager;
use MageOS\NetSuiteConnector\Core\Model\Process\ImportProcessor;

class Process extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \MageOS\NetSuiteConnector\Core\Test\Integration\Helper\NetSuiteServiceFaker
     */
    private static $netsuiteServiceFaker;

    /**
     * @var ServiceManagement|MockObject
     */
    private static $nsHelper;

    /**
     * Path to _files/_files_ns/... folders
     */
    private const RELATIVE_PATH_TO_FIXTURES = '../../../';

    /**
     * $netusiteServicerFaker is a replacement class for WSDL Netsuite class
     * $nsHelper is a mock because we use getNetSuiteService() call to get access to WSDL Netsuite class
     *
     * Because how Magento & phpunit works, we need to have them as static values. Main reason - the Mock is
     * cached but on the second test we create a new instance of the Mock but the old one is actually still active
     * in Magento code.
     */
    protected function setUp():void
    {
        $objectManager = Bootstrap::getObjectManager();

        if (!self::$netsuiteServiceFaker) {
            $path = realpath(__DIR__ . "/" . self::RELATIVE_PATH_TO_FIXTURES) . "/";
            self::$netsuiteServiceFaker = new \MageOS\NetSuiteConnector\Core\Test\Integration\Helper\NetSuiteServiceFaker($path);
        }
        if (!self::$nsHelper) {
            self::$nsHelper = $this->getMockBuilder(ServiceManagement::class)
                ->onlyMethods(['get', 'getServerTime'])
                ->disableOriginalConstructor()
                ->getMock();
        }
        self::$nsHelper->method('get')
            ->willReturn(self::$netsuiteServiceFaker);
        self::$nsHelper->method('getServerTime')
            ->willReturn('2024-02-02 00:00:00');

        // this is important to run per each test
        $objectManager->configure([ServiceManagement::class => ['shared' => true]]);
        $objectManager->addSharedInstance(self::$nsHelper, ServiceManagement::class);

        // Prepare ImportProcessor class to return no entries to process
        $importProcessor = $this->getMockBuilder(ImportProcessor::class)
                ->onlyMethods(['getImportableEntities'])
                ->disableOriginalConstructor()
                ->getMock();
        $importProcessor->method('getImportableEntities')
            ->willReturn([]);
        $objectManager->configure([ImportProcessor::class => ['shared' => true]]);
        $objectManager->addSharedInstance($importProcessor, ImportProcessor::class);
    }

    /**
     * @magentoConfigFixture default/mageos_netsuite/general/enabled 1
     * @magentoConfigFixture default/mageos_netsuite/queue_processing/updated_from_minutes 5
     * @magentoDbIsolation enabled
     */
    public function testProcessImportUpdatedFlag()
    {
        $objectManager = Bootstrap::getObjectManager();

        $flagManager = $objectManager->get(LastUpdateManager::class);
        $processModel = $objectManager->get(\MageOS\NetSuiteConnector\Core\Model\Process::class);

        // Set currently different date stored in DB
        $flagManager->setLastUpdateDate(LastUpdateManager::IMPORT_FLAG, '2024-01-01 00:00:00');
        $processModel->processImport(false);
        
        $this->assertEquals('2024-02-02 00:00:00', $flagManager->getLastUpdateDate(LastUpdateManager::IMPORT_FLAG));
    }

    /**
     * @magentoConfigFixture default/mageos_netsuite/general/enabled 1
     * @magentoConfigFixture default/mageos_netsuite/queue_processing/updated_from_minutes 5
     * @magentoDbIsolation enabled
     */
    public function testProcessImportNoMessages()
    {
        $objectManager = Bootstrap::getObjectManager();

        $processModel = $objectManager->get(\MageOS\NetSuiteConnector\Core\Model\Process::class);
        $processModel->processImport(false);

        $messageManagement = $objectManager->get(\MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface::class);
        $messages = $messageManagement->receive(\MageOS\NetSuiteConnector\Core\Enum\Message\Queue::IMPORT(), 10);
        $this->assertEquals(0, count($messages));
    }
}
