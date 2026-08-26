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

namespace MageOS\NetSuiteConnector\Inventory\Multi\Plugin;

use MageOS\NetSuiteConnector\Core\Command\NetSuiteCron as CoreNetSuiteCron;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\ResponseValidator;
use MageOS\NetSuiteConnector\Inventory\Multi\Model\Process\Import\Location;

/**
 * Class NetSuiteCron run location import\update.
 */
class NetSuiteCron
{
    private \MageOS\NetSuiteConnector\Inventory\Multi\Model\Process\Import\Location $location;
    private \MageOS\NetSuiteConnector\Core\Model\ProcessManagement $processManagement;
    private \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement;
    private \MageOS\NetSuiteConnector\Core\Model\Config\DeveloperConfig $developerConfig;

    public function __construct(
        private readonly \MageOS\NetSuiteConnector\Inventory\Model\Config\InventoryMode $inventoryMode,
        \MageOS\NetSuiteConnector\Inventory\Multi\Model\Process\Import\Location $location,
        \MageOS\NetSuiteConnector\Core\Model\ProcessManagement $processManagement,
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement,
        \MageOS\NetSuiteConnector\Core\Model\Config\DeveloperConfig $developerConfig
    ) {
        $this->location = $location;
        $this->processManagement = $processManagement;
        $this->serviceManagement = $serviceManagement;
        $this->developerConfig = $developerConfig;
    }

    /**
     * Run location import to Queue after specific mode is called
     *
     * @param CoreNetSuiteCron $subject
     * @param mixed $result
     * @param string $runMode
     *
     * @throws \MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException
     * @throws \MageOS\NetSuiteConnector\Core\Exception\NetSuiteRuntimeException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterProcessMode(
        CoreNetSuiteCron $subject,
        $result,
        string $runMode
    ): void {
        if (!$this->inventoryMode->isMulti()) {
            return;
        }

        if ($runMode == Location::MESSAGE_ACTION) {
            $this->importToQueue();
        }
    }

    /**
     * we process search in netsuite and put the result in the queue.
     * @TODO refactor after we change core logic for importToQueue
     * @throws \MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException
     * @throws \MageOS\NetSuiteConnector\Core\Exception\NetSuiteRuntimeException
     */
    private function importToQueue()
    {
        $netsuiteService = $this->serviceManagement->get();
        $netsuiteService->setSearchPreferences(false, (int)$this->developerConfig->getImportRecordLimit());
        $response = $netsuiteService->search($this->location->getNetsuiteRequest('location', ''));
        ResponseValidator::validate($response);
        $this->processManagement->processOtherEntities($this->location, $response->searchResult->recordList->record);
    }
}
