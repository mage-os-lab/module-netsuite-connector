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

namespace MageOS\NetSuiteConnector\Queue\Model\ResourceModel\Queue;

use Magento\MysqlMq\Model\QueueManagement;
use MageOS\NetSuiteConnector\Core\Enum\Message\Queue;
use MageOS\NetSuiteConnector\Core\Enum\Message\Status;

class Message extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('mageos_netsuite_message', 'message_id');
    }

    public function saveMessage(array $messageData): int
    {
        $connection = $this->getConnection();
        $connection->insert(
            $this->getMessageTable(),
            $messageData
        );
        return (int)$this->getConnection()->lastInsertId($this->getMessageTable());
    }

    public function getMessages(string $queue, int $limit): ?array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(['mageos_netsuite_message' => $this->getMessageTable()])
            ->where('queue = ?', $queue)
            ->where('status IN (?)', [(string)Status::IN_QUEUE(), Status::RETRY()])
            ->limit($limit)
            ->order('number_of_trials ASC')
            ->order('status ASC')
            ->order('priority ASC')
            ->order('message_id ASC');

        return $connection->fetchAll($select);
    }

    public function getMessage(array $conditions): ?array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(['mageos_netsuite_message' => $this->getMessageTable()]);

        $bind = [];
        $counter = 1;
        foreach ($conditions as $field => $value) {
            $select->where(sprintf('%1$s = :value_%2$s', $field, $counter));
            $bind['value_' . $counter] = $value;
            $counter++;
        }

        $data = $connection->fetchRow($select, $bind);
        return $data !== false ? $data : null;
    }

    public function getStuckMessages(int $interval = 1): ?array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(['mageos_netsuite_message' => $this->getMessageTable()])
            ->columns(['message_id', 'number_of_trials'])
            ->where('status = ?', (string)Status::IN_PROGRESS())
            ->where('picked_at < NOW() - INTERVAL ' . $interval . ' HOUR')
            ->limit(20);
        $data = $connection->fetchAll($select);
        return ($data === false) ? [] : $data;
    }

    public function changeStatus(array $messageIds, string $status)
    {
        $update = ['status' => $status];
        if ($status === (string)Status::IN_QUEUE()) {
            $update['number_of_trials'] = '0';
        }

        $this->getConnection()->update(
            $this->getMessageTable(),
            $update,
            ['message_id IN (?)' => $messageIds]
        );
    }

    /**
     * Mark specified messages with 'in progress' status.
     *
     * @param int[] $messageIds
     * @return int[] IDs of messages which should be taken in progress by current process.
     */
    public function markMessagesInProgress(array $messageIds): array
    {
        $takenMessageIds = [];
        foreach ($messageIds as $messageId) {
            $affectedRows = $this->getConnection()->update(
                $this->getMessageTable(),
                [
                    'status' => (string)Status::IN_PROGRESS()
                ],
                ['message_id = ?' => $messageId]
            );
            if ($affectedRows) {
                /**
                 * If status was set to 'in progress' by some other process (due to race conditions),
                 * current process should not process the same message.
                 * So message will be processed only if current process was able to change its status.
                 */
                $takenMessageIds[] = $messageId;

                /**
                 * Because we want to rise the counter to see how many times we are processing the same
                 * message, we need a separate update otherwise $affectedRows will always return = 1 as the counter
                 * always gets updated
                 */
                $this->getConnection()->update(
                    $this->getMessageTable(),
                    [
                        'number_of_trials' => new \Zend_Db_Expr('number_of_trials + 1'),
                        'picked_at' => new \Zend_Db_Expr('NOW()')
                    ],
                    ['message_id = ?' => $messageId]
                );
            }
        }
        return $takenMessageIds;
    }

    public function deleteMessages(array $messageIds)
    {
        $connection = $this->getConnection();
        return $connection->delete($this->getMessageTable(), ['message_id IN (?)' => $messageIds]);
    }

    /**
     * Get name of table storing message body and topic.
     *
     * @return string
     */
    protected function getMessageTable()
    {
        return $this->getTable('mageos_netsuite_message');
    }

    public function getMessagesByConditions(array $conditions) : ?array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(['mageos_netsuite_message' => $this->getMessageTable()]);

        foreach ($conditions as $field => $value) {
            $select->where($connection->quoteInto($field . ' = ?', $value));
        }
        $data = $connection->fetchAll($select);
        return $data !== false ? $data : null;
    }
}
