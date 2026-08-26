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
namespace MageOS\NetSuiteConnector\Core\Command\Util;

use MageOS\NetSuiteConnector\Core\Command\AbstractNSCommand;
use MageOS\NetSuiteConnector\Core\Exception\MessageProcessor;
use MageOS\NetSuiteConnector\Core\Model\Command\Utils\ProcessRecord\RecordProcessorInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use MageOS\NetSuiteConnector\Core\Enum\Record\DateRange;
use Magento\Framework\Console\Cli;

class ProcessRecordsByDateRange extends AbstractNSCommand
{
    private const DEFAULT_BATCH_SIZE = 50;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger,
        private array $cliProcessors = [],
        private array $cliProcessorNames = []
    ) {
        parent::__construct($context, $logger);
        $this->cliProcessorNames = array_keys($this->cliProcessors);
    }

    protected function configure(): void
    {
        $this->setName('netsuite:utils:importdaterange')
            ->setDescription('Import NetSuite records by specific period of time.')
            ->setDefinition([
                new InputOption(
                    DateRange::TYPE->value,
                    't',
                    InputOption::VALUE_REQUIRED,
                    'The type of the NetSuite record(e.g. invoice_import, cashsale_import, etc...)'
                ),
                new InputOption(
                    DateRange::FROM_DATE->value,
                    'f',
                    InputOption::VALUE_REQUIRED,
                    'Import records from NS by date interval, it is start date.'
                ),
                new InputOption(
                    DateRange::TO_DATE->value,
                    'e',
                    InputOption::VALUE_REQUIRED,
                    'Import records from NS by date interval, it is end date.'
                ),
                new InputOption(
                    DateRange::BATCH_SIZE->value,
                    'b',
                    InputOption::VALUE_OPTIONAL,
                    'The batch size.'
                )
            ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);

        try {
            if (!$input->getOption(DateRange::TYPE->value)) {
                throw new \InvalidArgumentException('Missing type argument.');
            }

            if (!$input->getOption(DateRange::FROM_DATE->value)) {
                throw new \InvalidArgumentException('Missing start date argument.');
            }

            if (!$input->getOption(DateRange::TO_DATE->value)) {
                throw new \InvalidArgumentException('Missing end date argument.');
            }

            $args = [
                DateRange::TYPE->value => $input->getOption(DateRange::TYPE->value),
                DateRange::FROM_DATE->value => $input->getOption(DateRange::FROM_DATE->value),
                DateRange::TO_DATE->value => $input->getOption(DateRange::TO_DATE->value),
                DateRange::BATCH_SIZE->value => $input->getOption(DateRange::BATCH_SIZE->value)
                    ?? self::DEFAULT_BATCH_SIZE
            ];

            if (!in_array($args[DateRange::TYPE->value], $this->cliProcessorNames)) {
                $this->logger->error(
                    'type must be specified and must be one of: ' . implode(',', $this->cliProcessorNames)
                );
                return Cli::RETURN_FAILURE;
            }

            /** @var RecordProcessorInterface $cliProcessor */
            $cliProcessor = $this->cliProcessors[$args[DateRange::TYPE->value]];
            if (!($cliProcessor instanceof RecordProcessorInterface)) {
                $this->logger->error(
                    'Processor for ' . $args[DateRange::TYPE->value] . ' is not instance of '
                    . RecordProcessorInterface::class
                );
                return Cli::RETURN_FAILURE;
            }

            $output->writeln(sprintf('<info>Start to process record type - %s</info>', $args[DateRange::TYPE->value]));
            $cliProcessor->execute($args);
            $output->writeln(sprintf('<info>End to process record type - %s</info>', $args[DateRange::TYPE->value]));
            return Cli::RETURN_SUCCESS;
        } catch (\Throwable $e) {
            $message = MessageProcessor::getMessagesAsString($e);
            $this->logger->error($message);
            return Cli::RETURN_FAILURE;
        }
    }
}
