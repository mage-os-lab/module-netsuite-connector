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

namespace MageOS\NetSuiteConnector\Core\Command;

use Magento\Framework\Exception\LocalizedException;
use MageOS\NetSuiteConnector\Core\Exception\MessageProcessor;
use MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig;
use MageOS\NetSuiteConnector\Core\Model\MutexFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class NetSuiteCron - CLI to provide import/export queue processing via cron
 */
class NetSuiteCron extends AbstractNSCommand
{
    private const INPUT_KEY_MODE = 'mode';
    private const INPUT_KEY_DEBUG = 'debug';

    /**
     * @var ConnectorConfig
     */
    private $connectorConfig;
    /**
     * @var array
     */
    private $possibleModes;
    /**
     * @var MutexFactory
     */
    private $mutexFactory;
    /**
     * @var \MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry
     */
    private $registry;
    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\Process
     */
    private $process;

    /**
     * NetSuiteCron constructor.
     * @param \MageOS\NetSuiteConnector\Core\Model\Process\Proxy $process
     * @param \MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry $registry
     * @param ConnectorConfig $connectorConfig
     * @param \MageOS\NetSuiteConnector\Core\Model\MutexFactory $mutexFactory
     * @param array $possibleModes
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Core\Model\Process\Proxy $process,// phpcs:ignore
        \MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry $registry,
        \MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig $connectorConfig,
        \MageOS\NetSuiteConnector\Core\Model\MutexFactory $mutexFactory,
        $possibleModes = []
    ) {
        parent::__construct();
        $this->connectorConfig = $connectorConfig;
        $this->mutexFactory = $mutexFactory;
        $this->registry = $registry;
        $this->process = $process;
        $this->possibleModes = $possibleModes;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('netsuite:cron')
            ->setDescription('Runs NetSuite synchronization');
        $this->setDefinition([
            new InputOption(
                self::INPUT_KEY_MODE,
                'm',
                InputOption::VALUE_REQUIRED,
                'Run mode. Can be all, import, export, stock or any comma delimited combination that contains them'
            ),
            new InputOption(
                self::INPUT_KEY_DEBUG,
                'd',
                InputOption::VALUE_OPTIONAL,
                'Debug the process - output to CLI instead of the log.'
            ),
        ]);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->isModuleEnabled($this->connectorConfig, $output)) {
            return 0;
        }

        $this->setAdminAppArea();

        $modes = $this->getModes($input);
        if (!$modes) {
            $this->logger->addError('Incorrect mode specification');
            return 126;
        }

        $mutex = $this->mutexFactory->createQueueMutex($modes);
        if (!$mutex->getLock()) {
            $this->logger->addInfo('Mutex cant get a lock.');
            return 126;
        }

        foreach ($modes as $mode) {
            $this->registry->unregister('current_run_mode');
            $this->registry->register('current_run_mode', $mode);
            $this->processMode($mode);
        }
        return 0;
    }

    /**
     * Process import/export mode.
     *
     * @param string $mode
     */
    public function processMode(string $mode): void
    {
        switch ($mode) {
            case 'all':
                $this->processImport();
                $this->processExport();
                break;
            case 'import':
                $this->processImport();
                break;
            case 'export':
                $this->processExport();
                break;
            case 'importToQueue':
                $this->processImportToQueue();
                break;
            case 'processQueue':
                $this->processImportQueue();
                break;
        }
    }

    protected function processImportToQueue(): void
    {
        try {
            $this->process->processImport(false);
        } catch (\Throwable $e) {
            $this->logger->addError(MessageProcessor::getMessagesAsString($e));
        }
    }

    protected function processImportQueue(): void
    {
        try {
            $this->process->processImportQueue();
        } catch (\Throwable $e) {
            $this->logger->addError(MessageProcessor::getMessagesAsString($e));
        }
    }

    protected function processImport(): void
    {

        try {
            $this->process->processImportQueue();
            $this->process->processImport(false);
        } catch (\Throwable $e) {
            $this->logger->addError(MessageProcessor::getMessagesAsString($e));
        }
    }

    protected function processExport(): void
    {
        try {
            $this->process->processExport();
        } catch (\Throwable $e) {
            $this->logger->addError(MessageProcessor::getMessagesAsString($e));
        }
    }

    /**
     * @param InputInterface $input
     * @return array|null
     */
    protected function getModes(InputInterface $input): ?array
    {
        $result = null;
        $modesString = $input->getOption(self::INPUT_KEY_MODE);
        if (!$modesString) {
            return $result;
        }
        $ret = [];
        $modes = explode(',', $modesString);
        $possibleModes = $this->possibleModes;
        foreach ($modes as $mode) {
            if (in_array(trim($mode), $possibleModes)) {
                $ret[] = trim($mode);
            }
        }
        if (!count($ret)) {
            return $result;
        }
        $result = $ret;
        //If all is one of the modes, remove the others as it will only create duplication
        if (in_array('all', $ret)) {
            $result = ['all'];
        }
        return $result;
    }
}
