<?php declare(strict_types=1);
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
namespace MageOS\NetSuiteConnector\Core\Cron;

use MageOS\NetSuiteConnector\Core\Model\Monitor\Data\Status;

/**
 * Cron- trigger 'clean monitor' service based on two arguments
 * - statusList - array of monitor record statuses
 * - days - It is date: now - days
 * Cron works one time every day
 */
class CleanMonitor
{
    private \MageOS\NetSuiteConnector\Core\Model\Config\MonitorConfig $monitorConfig;

    private \MageOS\NetSuiteConnector\Core\Model\Monitor\CleanMonitorService $cleanMonitorService;

    public function __construct(
        \MageOS\NetSuiteConnector\Core\Model\Config\MonitorConfig $monitorConfig,
        \MageOS\NetSuiteConnector\Core\Model\Monitor\CleanMonitorService $cleanMonitorService
    ) {
        $this->monitorConfig = $monitorConfig;
        $this->cleanMonitorService = $cleanMonitorService;
    }

    public function execute(): void
    {
        $days = $this->getDays() ?: 0;
        $statuses = $this->getStatuses();
        $date = date('Y-m-d', strtotime("-{$days} day"));
        if ($statuses && $date) {
            $this->cleanMonitorService->execute($statuses, $date);
        }
    }

    private function getStatuses(): array
    {
        $statuses = Status::getLabels();
        if (array_key_exists('in_queue', $statuses)) {
            unset($statuses['in_queue']);
        }
        return array_keys($statuses);
    }

    private function getDays(): int
    {
        return (int)$this->monitorConfig->getLifetime();
    }
}
