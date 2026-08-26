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
namespace MageOS\NetSuiteConnector\Core\Command\Maintenance;

use MageOS\NetSuiteConnector\Core\Command\AbstractNSCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use MageOS\NetSuiteConnector\Core\Model\Monitor\Data\Status;

/**
 * CLI command - trigger 'clean monitor' service based on two arguments
 * - statusList - array of monitor record statuses
 * - days - It is date: now - days
 */
class CleanMonitor extends AbstractNSCommand
{
    private const STATUS_LIST = 'statusList';
    private const DAYS = 'days';

    private \MageOS\NetSuiteConnector\Core\Model\Monitor\CleanMonitorService $cleanMonitorService;

    public function __construct(
        \MageOS\NetSuiteConnector\Core\Model\Monitor\CleanMonitorService $cleanMonitorService,
        ?\Magento\Framework\Model\Context $context = null,
        ?\MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger = null
    ) {
        parent::__construct($context, $logger);
        $this->cleanMonitorService = $cleanMonitorService;
    }

    protected function configure(): void
    {
        $this->setName('netsuite:maintenance:cleanmonitor')
            ->setDescription('Manually cleans monitor records by status, the same logic as cronjob')
            ->addOption(
                self::STATUS_LIST,
                null,
                InputOption::VALUE_OPTIONAL,
                'Comma separated list of record statuses in monitor'
            )
            ->addOption(
                self::DAYS,
                null,
                InputOption::VALUE_OPTIONAL,
                'Older then X days'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $exitCode = 0;

        try {
            $days = (int)$input->getOption(self::DAYS);
            $date = date('Y-m-d h:i:s', strtotime("-{$days} day"));
            $statuses = $input->getOption(self::STATUS_LIST) ?: '';
            $statuses = $this->getStatuses($statuses);
            $output->writeln(sprintf('<info>Provided date is %s</info>', $date));
            $output->writeln(sprintf('<info>Provided statuses are %s</info>', implode(',', $statuses)));
            $output->writeln('<info>Start cleaning...</info>');
            $this->cleanMonitorService->execute($statuses, $date);
            $output->writeln('<info>Finish cleaning successfully</info>');
        } catch (\Throwable $exception) {
            $exitCode = 1;
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));
        }

        return $exitCode;
    }

    private function getStatuses(string $statusList): array
    {
        $statuses = [];
        $possibleStatuses = array_keys(Status::getLabels());
        if (!$statusList) {
            foreach ($possibleStatuses as $key => $value) {
                if ($value === 'in_queue') {
                    unset($possibleStatuses[$key]);
                }
            }
            $statuses = $possibleStatuses;
        }

        if ($statusList) {
            $statusList = explode(',', $statusList);
            foreach ($statusList as $status) {
                if (in_array($status, $possibleStatuses)) {
                    $statuses[] = $status;
                }
            }
        }

        return $statuses;
    }
}
