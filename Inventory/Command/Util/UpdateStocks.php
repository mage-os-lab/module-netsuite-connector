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


namespace MageOS\NetSuiteConnector\Inventory\Command\Util;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use MageOS\NetSuiteConnector\Core\Command\AbstractNSCommand;

/**
 * Class UpdateStocks uses to declare CLI command and run stock updates.
 */
class UpdateStocks extends AbstractNSCommand
{
    private \MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig $connectorConfig;
    private \MageOS\NetSuiteConnector\Inventory\Model\Process\Import\Stock $stockProcessor;

    /**
     * UpdateStocks constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \MageOS\NetSuiteConnector\Inventory\Model\Process\Import\Stock $stockProcessor
     * @param \MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig $connectorConfig
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \MageOS\NetSuiteConnector\Inventory\Model\Process\Import\Stock $stockProcessor,
        \MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig $connectorConfig
    ) {
        parent::__construct($context);
        $this->stockProcessor = $stockProcessor;
        $this->connectorConfig = $connectorConfig;
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('netsuite:utils:updatestocks')
            ->setDescription('Manually updates stock information');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->isModuleEnabled($this->connectorConfig, $output)) {
            return \Symfony\Component\Console\Command\Command::FAILURE;
        }

        $this->setAdminAppArea();
        $this->stockProcessor->process();

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }
}
