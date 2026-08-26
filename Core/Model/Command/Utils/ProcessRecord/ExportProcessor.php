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

namespace MageOS\NetSuiteConnector\Core\Model\Command\Utils\ProcessRecord;

use MageOS\NetSuiteConnector\Core\Model\Command\Utils\ProcessRecord\RecordProcessorInterface;

class ExportProcessor implements RecordProcessorInterface
{
    private \MageOS\NetSuiteConnector\Core\Api\Data\MessageInterfaceFactory $messageFactory;
    private \MageOS\NetSuiteConnector\Core\Model\Process\Export\ExportProcessorInterface $exportModel;

    public function __construct(
        \MageOS\NetSuiteConnector\Core\Api\Data\MessageInterfaceFactory $messageFactory,
        \MageOS\NetSuiteConnector\Core\Model\Process\Export\ExportProcessorInterface $exportModel
    ) {
        $this->messageFactory = $messageFactory;
        $this->exportModel = $exportModel;
    }

    public function execute(array $recordIds): void
    {
        foreach ($recordIds as $recordId) {
            /** @var \MageOS\NetSuiteConnector\Core\Api\Data\MessageInterface $message */
            $message = $this->messageFactory->create();
            $message->setData('item_id', (int)$recordId);

            $this->exportModel->process($message);
        }
    }
}
