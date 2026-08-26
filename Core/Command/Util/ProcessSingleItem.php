<?php
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

declare(strict_types=1);

namespace MageOS\NetSuiteConnector\Core\Command\Util;

use MageOS\NetSuiteConnector\Core\Model\ImportQueueManager;
use MageOS\NetSuiteConnector\Core\Model\Process\ExportProcessor;
use MageOS\NetSuiteConnector\Core\Model\Process\ImportProcessor;
use MageOS\NetSuiteConnector\Core\Exception\SkipRecordException;
use Symfony\Component\Console\Input\InputInterface;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ProcessSingleItem - CLI to process single item of any type from the NSC Queue
 */
class ProcessSingleItem extends \MageOS\NetSuiteConnector\Core\Command\AbstractNSCommand
{
    private const INPUT_KEY_MODE = 'mode';
    private const INPUT_KEY_MESSAGE_ID = 'message-id';

    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\ImportQueueManager
     */
    protected $importQueue;
    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\Process\ImportProcessor
     */
    protected $importProcessor;
    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\Process\ExportProcessor
     */
    protected $exportProcessor;
    /**
     * @var \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface
     */
    protected $messageManagement;

    /**
     * ProcessSingleItem constructor.
     * @param ImportQueueManager\Proxy $importQueue
     * @param \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement
     * @param ImportProcessor $importProcessor
     * @param ExportProcessor $exportProcessor
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Core\Model\ImportQueueManager $importQueue,
        \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement,
        \MageOS\NetSuiteConnector\Core\Model\Process\ImportProcessor $importProcessor,
        \MageOS\NetSuiteConnector\Core\Model\Process\ExportProcessor $exportProcessor
    ) {
        parent::__construct();
        $this->importQueue = $importQueue;
        $this->importProcessor = $importProcessor;
        $this->exportProcessor = $exportProcessor;
        $this->messageManagement = $messageManagement;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('netsuite:utils:processsingleitem')
            ->setDescription('Process a single item from the import or export queue');

        $this->setDefinition([
            new InputOption(self::INPUT_KEY_MODE, 'm', InputOption::VALUE_REQUIRED, 'Mode, either import or export'),
            new InputOption(self::INPUT_KEY_MESSAGE_ID, 's', InputOption::VALUE_REQUIRED, 'The queue message id')
        ]);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        $this->setAdminAppArea();

        $mode = $input->getOption(self::INPUT_KEY_MODE);

        if (!in_array($mode, ['import','export'])) {
            $this->logger->error('mode must be specified and be either import or export');
            return \Symfony\Component\Console\Command\Command::FAILURE;
        }

        $messageId = (int)$input->getOption(self::INPUT_KEY_MESSAGE_ID);
        if (!$messageId) {
            $this->logger->error('Please specify a message id');
            return \Symfony\Component\Console\Command\Command::FAILURE;
        }

        $status = false;
        if ($mode == 'import') {
            $status = $this->importSingleMessage($messageId);
        } elseif ($mode == 'export') {
            $status = $this->exportSingleMessage($messageId);
        }

        if ($status) {
            $this->logger->info('Message processed successfully!');
            return \Symfony\Component\Console\Command\Command::SUCCESS;
        }

        return \Symfony\Component\Console\Command\Command::FAILURE;
    }

    /**
     * @param $messageId
     * @return bool
     */
    protected function exportSingleMessage(int $messageId):bool
    {
        $message = $this->messageManagement->getMessageById($messageId);
        $processModel = $this->exportProcessor->getEntityProcessor($message->getAction());

        try {
            $processModel->process($message);
            $this->messageManagement->deleteById($messageId);
        } catch (\Throwable $ex) {
            $this->logger->error($ex->getMessage());
            $this->messageManagement->reject([$messageId], $ex->getMessage());
            return false;
        }

        return true;
    }

    /**
     * @param $messageId
     * @return bool
     * @throws \MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException
     */
    protected function importSingleMessage(int $messageId): bool
    {
        $message = $this->messageManagement->getMessageById($messageId);
        $processModel = $this->importProcessor->getEntityProcessor($message->getAction());

        try {
            $rows = $processModel->process($message->getObject());
            if ($rows) {
                $this->importQueue->import($rows);
                $this->importQueue->commit();
            }
            $this->messageManagement->deleteById($messageId);
        } catch (SkipRecordException $ex) {
            $this->logger->error('Skip record: ' . $ex->getMessage());
            $this->messageManagement->deleteById($messageId);
            return false;
        } catch (\Throwable $ex) {
            $this->logger->error($ex->getMessage());
            $this->messageManagement->reject([$messageId], $ex->getMessage());
            return false;
        }

        return true;
    }
}
