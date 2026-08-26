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
namespace MageOS\NetSuiteConnector\Core\Model\Monitor;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;

/**
 * Clean Monitor Service
 *  - remove records by 3 conditions:
 * 1. A record doesn't exist in mageos_netsuite_message table
 * 2. Records that have specific value of status field (first argument)
 * 3. Records that are older than specific date (second argument)
 */
class CleanMonitorService
{
    private \Magento\Framework\App\ResourceConnection $resourceConnection;
    private ?\Magento\Framework\DB\Adapter\AdapterInterface $adapter = null;

    public function __construct(\Magento\Framework\App\ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    public function execute(array $statuses, string $date): void
    {
        $connection = $this->getConnection();
        $select = $this->prepareSelect($statuses, $date);
        $connection->query($connection->deleteFromSelect($select, 't1'));
    }

    private function prepareSelect(array $statuses, string $date): Select
    {
        $connection = $this->getConnection();
        return $connection->select()
            ->from(
                ['t1' => $this->resourceConnection->getTableName('mageos_netsuite_monitor')],
                ['t1.monitor_id']
            )
            ->joinLeft(
                ['t2' => $this->resourceConnection->getTableName('mageos_netsuite_message')],
                't1.message_id = t2.message_id',
                []
            )
            ->where('t1.status IN (?)', $statuses)
            ->where('t1.created_at < (?)', $date)
            ->where('t2.message_id IS NULL');
    }

    private function getConnection(): AdapterInterface
    {
        if (!$this->adapter) {
            $this->adapter = $this->resourceConnection->getConnection();
        }
        return $this->adapter;
    }
}
