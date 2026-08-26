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

namespace MageOS\NetSuiteConnector\Queue\Model\ResourceModel\Monitor;

use Magento\MysqlMq\Model\QueueManagement;
use MageOS\NetSuiteConnector\Core\Enum\Message\Queue;
use MageOS\NetSuiteConnector\Core\Enum\Message\Status;

class FastMonitorItem extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('mageos_netsuite_monitor', 'monitor_id');
    }

    public function changeStatus(int $messageId, string $status, string $message, $triggerCounter = false)
    {
        $update = [
            'status' => $status,
            'process_output' => $message
        ];
        if ($triggerCounter) {
            $update['counter'] = new \Zend_Db_Expr('counter + 1');
        }

        $this->changeStatusWithData($messageId, $update);
    }

    public function changeStatusWithData(int $messageId, array $update)
    {
        $this->getConnection()->update(
            $this->getMonitorTable(),
            $update,
            ['message_id = (?)' => $messageId]
        );
    }

    public function saveMonitorItem(array $monitorItemData): int
    {
        $connection = $this->getConnection();
        $connection->insert(
            $this->getMonitorTable(),
            $monitorItemData
        );
        return (int)$this->getConnection()->lastInsertId($this->getMonitorTable());
    }

    /**
     * Get name of table storing message body and topic.
     *
     * @return string
     */
    protected function getMonitorTable()
    {
        return $this->getTable('mageos_netsuite_monitor');
    }
}
