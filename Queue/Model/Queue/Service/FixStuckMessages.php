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

namespace MageOS\NetSuiteConnector\Queue\Model\Queue\Service;

use MageOS\NetSuiteConnector\Core\Enum\Message\Status;

class FixStuckMessages implements \MageOS\NetSuiteConnector\Core\Model\Service\Queue\FixStuckMessagesInterface
{
    public function __construct(
        private readonly \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement
    ) {
    }

    public function execute() : void
    {
        while (true) {
            $stuckMessages = $this->messageManagement->getStuckMessages();

            if (count($stuckMessages) === 0) {
                return;
            }
            $this->processMessagesBatch($stuckMessages);
        }
    }

    private function processMessagesBatch(array $messages) : void
    {
        $messageIdsToRetry = [];
        $messageIdsToError = [];
        foreach ($messages as $messageRow) {
            if (!array_key_exists('message_id', $messageRow) || !array_key_exists('number_of_trials', $messageRow)) {
                // We have invalid structure returned
                return;
            }
            if ((int)$messageRow['number_of_trials'] === 1) {
                $messageIdsToRetry[] = $messageRow['message_id'];
            } else {
                $messageIdsToError[] = $messageRow['message_id'];
            }
        }
        if (count($messageIdsToError) > 0) {
            $this->messageManagement->changeStatus(
                $messageIdsToError,
                Status::ERROR(),
                'Putting stuck message into Error state'
            );
            foreach ($messageIdsToError as $errorMessageId) {
                $this->messageManagement->deleteById((int)$errorMessageId);
            }
        }
        if (count($messageIdsToRetry) > 0) {
            $this->messageManagement->changeStatus(
                $messageIdsToRetry,
                Status::RETRY(),
                'Putting stuck message back in queue'
            );
        }
    }
}
