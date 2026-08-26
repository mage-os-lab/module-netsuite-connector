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

namespace MageOS\NetSuiteConnector\Order\Test\Integration\Model;

use Magento\TestFramework\Helper\Bootstrap;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\LastUpdateManager;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Repository as NetSuiteRepository;
use MageOS\NetSuiteConnector\Core\Model\Process\ImportProcessor;
use MageOS\NetSuiteConnector\Order\Model\Process\Import\Order as OrderImport;

class CoreImportOrderMessage extends \PHPUnit\Framework\TestCase
{
    /**
     * @magentoConfigFixture default/mageos_netsuite/general/enabled 1
     * @magentoConfigFixture default/mageos_netsuite/queue_processing/updated_from_minutes 5
     * @magentoConfigFixture default/mageos_netsuite/enable_disable_features/get_order_changes 1
     * @magentoDbIsolation enabled
     */
    public function testProcessImportOrderMessageFull() : void
    {
        $objectManager = Bootstrap::getObjectManager(); // @phpstan-ignore-line

        // Prepare NetSuite Repository to return single SalesOrder record
        $netSuiteRepository = $this->getMockBuilder(NetSuiteRepository::class)
                ->onlyMethods(['fetchMultipleRecordsFromNetSuite', 'getServerTime'])
                ->disableOriginalConstructor()
                ->getMock();
        $netSuiteRepository->method('fetchMultipleRecordsFromNetSuite')
            ->willReturn([$this->getNetsuiteResponseObject()]);
        $netSuiteRepository->method('getServerTime')
            ->willReturn('2024-02-02 00:00:00');
        $objectManager->configure([NetSuiteRepository::class => ['shared' => true]]);
        $objectManager->addSharedInstance($netSuiteRepository, NetSuiteRepository::class);

        // Prepare Order Import processor
        $orderImport = $this->getMockBuilder(OrderImport::class)
                ->onlyMethods(['isMagentoImportable', 'isAlreadyImported', 'getRecordType', 'queryNetsuite'])
                ->disableOriginalConstructor()
                ->getMock();
        $orderImport->method('isMagentoImportable')
            ->willReturn(true);
        $orderImport->method('isAlreadyImported')
            ->willReturn(false);
        $orderImport->method('getRecordType')
            ->willReturn(\NetSuite\Classes\RecordType::salesOrder);
        $orderImport->method('queryNetsuite')
            ->willReturn($this->returnCallback([$this, 'returnRecordsCallback']));

        // Prepare ImportProcessor
        $importProcessor = $this->getMockBuilder(ImportProcessor::class)
                ->onlyMethods(['getImportableEntities'])
                ->disableOriginalConstructor()
                ->getMock();
        $importProcessor->method('getImportableEntities')
            ->willReturn([$orderImport]);
        $objectManager->configure([ImportProcessor::class => ['shared' => true]]);
        $objectManager->addSharedInstance($importProcessor, ImportProcessor::class);

        // Prepare LastUpdateManager
        $lastUpdateManager = $this->getMockBuilder(LastUpdateManager::class)
                ->onlyMethods(['setLastUpdateDate', 'getLastUpdateDate'])
                ->disableOriginalConstructor()
                ->getMock();
        $lastUpdateManager->method('setLastUpdateDate')
            ->willReturn(null);
        $lastUpdateManager->method('getLastUpdateDate')
            ->willReturn(null);
        $objectManager->configure([LastUpdateManager::class => ['shared' => true]]);
        $objectManager->addSharedInstance($lastUpdateManager, LastUpdateManager::class);

        // Run process
        $process = $objectManager->get(\MageOS\NetSuiteConnector\Core\Model\Process::class);
        $process->processImport(false);

        // Do assertions
        $messageManagement = $objectManager->get(\MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface::class);
        $messages = $messageManagement->receive(\MageOS\NetSuiteConnector\Core\Enum\Message\Queue::IMPORT(), 10);
        $this->assertEquals(1, count($messages));
        $this->assertEquals(7855985, $messages[0]->getItemId());
        $this->assertEquals(\MageOS\NetSuiteConnector\Core\Enum\Message\Status::IN_QUEUE(), $messages[0]->getStatus());
        $this->assertEquals("Pending Approval", $messages[0]->getBody()->status);
        foreach ($messages[0]->getBody()->itemList->item as $nsItem) {
            $this->assertEquals(0, $nsItem->quantityBilled);
            $this->assertEquals(0, $nsItem->quantityFulfilled);
        }
    }

    private function createNetsuiteRecord() : \NetSuite\Classes\SalesOrder
    {
        $record = new \NetSuite\Classes\SalesOrder();
        $record->internalId = 7855985;
        return $record;
    }

    private function getNetsuiteResponseObject() : \NetSuite\Classes\SalesOrder
    {
        $file = __DIR__ . "/../_files_ns_response/Order";
        $content = file_get_contents($file);
        $serialized = rtrim(str_replace("\r", "", $content));
        return unserialize($serialized); // phpcs:ignore
    }

    public function returnRecordsCallback() : array
    {
        $args = func_get_args();
        $fromBeginning = isset($args[1]) ? $args[1] : false;
        if ($fromBeginning) {
            return [$this->createNetsuiteRecord()];
        } else {
            return [];
        }
    }
}
