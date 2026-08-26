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

namespace MageOS\NetSuiteConnector\Core\Command\Util;

use MageOS\NetSuiteConnector\Core\Command\AbstractNSCommand;
use MageOS\NetSuiteConnector\Core\Exception\MessageProcessor;
use MageOS\NetSuiteConnector\Core\Model\Command\Utils\ProcessRecord\RecordProcessorInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessRecord extends AbstractNSCommand
{
    private const INPUT_RECORD_TYPE = 'type';
    private const INPUT_RECORD_ID = 'id';

    private array $cliProcessors;
    private array $cliProcessorNames;

    public function __construct(
        array $cliProcessors = []
    ) {
        $this->cliProcessors = $cliProcessors;
        $this->cliProcessorNames = array_keys($this->cliProcessors);
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('netsuite:utils:processrecord')
            ->setDescription('Process a record (or CSV list of records) based on the type');

        $types = empty($this->cliProcessorNames) ? ['n/a'] : $this->cliProcessorNames;
        $this->setDefinition([
            new InputOption(
                self::INPUT_RECORD_TYPE,
                't',
                InputOption::VALUE_REQUIRED,
                'Record types: ' . implode(',', $types)
            ),
            new InputOption(
                self::INPUT_RECORD_ID,
                's',
                InputOption::VALUE_REQUIRED,
                'The record ID. Either Magento record ID or NetSuite record Internal ID (based on mode)'
            )
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

        $recordType = $input->getOption(self::INPUT_RECORD_TYPE);
        if (!in_array($recordType, $this->cliProcessorNames)) {
            $this->logger->error(
                'type must be specified and must be one of: ' . implode(',', $this->cliProcessorNames)
            );
            return \Symfony\Component\Console\Command\Command::FAILURE;
        }

        $recordIds = trim((string)$input->getOption(self::INPUT_RECORD_ID));
        if (empty($recordIds)) {
            $this->logger->error('id must be specified');
            return \Symfony\Component\Console\Command\Command::FAILURE;
        }

        /** @var RecordProcessorInterface $cliProcessor */
        $cliProcessor = $this->cliProcessors[$recordType];
        if (!($cliProcessor instanceof RecordProcessorInterface)) {
            $this->logger->error(
                'Processor for ' . $recordType . ' is not instance of ' . RecordProcessorInterface::class
            );
            return \Symfony\Component\Console\Command\Command::FAILURE;
        }

        try {
            $recordIds = array_filter(explode(',', $recordIds), 'trim');
            $cliProcessor->execute($recordIds);
        } catch (\Throwable $e) {
            $message = MessageProcessor::getMessagesAsString($e);
            $this->logger->error($message);
            return \Symfony\Component\Console\Command\Command::FAILURE;
        }

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }
}
