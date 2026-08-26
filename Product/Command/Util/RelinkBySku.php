<?php
/*
 *  RocketWeb
 *
 *  NOTICE OF LICENSE
 *
 *  This source file is subject to the Open Software License (OSL 3.0)
 *  that is bundled with this package in the file LICENSE.txt.
 *  It is also available through the world-wide-web at this URL:
 *  http://opensource.org/licenses/osl-3.0.php
 *
 *  @category  RocketWeb
 *  @package   MageOS_NetSuiteConnector
 *  @copyright Copyright (c) 2026 RocketWeb (http://rocketweb.com)
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  @author    Rocket Web Inc.
 */

declare(strict_types=1);

namespace MageOS\NetSuiteConnector\Product\Command\Util;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command that loads all existing Magento products and assigns netsuite_internal_id if match is found
 */
class RelinkBySku extends \MageOS\NetSuiteConnector\Core\Command\AbstractNSCommand
{
    private const INPUT_KEY_DRY_RUN = 'dry-run';
    private const INPUT_KEY_NETSUITE_SKU_FIELD = 'netsuite-sku-field';
    private const INPUT_KEY_START_PRODUCT_ID = 'start_id';
    private const INPUT_KEY_BATCH_SIZE = 'batch-size';
    private const INPUT_KEY_FIELD_CUSTOM = 'sku-custom';
    /**
     * @var  \MageOS\NetSuiteConnector\Product\Model\Command\RelinkProcessor
     */
    private $relinkProcessor;
    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig
     */
    private $connectorConfig;

    /**
     * RelinkProductsBySku constructor.
     * @param \MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger
     * @param \Magento\Framework\Model\Context $context
     * @param \MageOS\NetSuiteConnector\Product\Model\Command\RelinkProcessor $relinkProcessor
     * @param \MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig $connectorConfig
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger,
        \Magento\Framework\Model\Context $context,
        \MageOS\NetSuiteConnector\Product\Model\Command\RelinkProcessor $relinkProcessor,
        \MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig $connectorConfig
    ) {
        parent::__construct($context, $logger);
        $this->relinkProcessor = $relinkProcessor;
        $this->connectorConfig = $connectorConfig;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('netsuite:utils:relinkbysku')->setDescription(
            'Adds or changes existing products netsuite internal ids based on sku'
        );

        $this->setDefinition([
            new InputArgument(
                self::INPUT_KEY_DRY_RUN,
                InputArgument::OPTIONAL,
                'If specified, no changes will be done,' .
                    'the command will only describe the operations that should happen'
            ),
            new InputOption(
                self::INPUT_KEY_FIELD_CUSTOM,
                'f',
                InputOption::VALUE_OPTIONAL,
                'Should be specified if sku is custom field of Items',
                0
            ),
            new InputOption(
                self::INPUT_KEY_NETSUITE_SKU_FIELD,
                's',
                InputOption::VALUE_REQUIRED,
                'An Inventory Item standard field where the SKU is kept in NetSuite. Defaults to itemId ',
                'itemId'
            ),
            new InputOption(
                self::INPUT_KEY_START_PRODUCT_ID,
                'o',
                InputOption::VALUE_OPTIONAL,
                'Product entity_id to start import from',
                0
            ),
            new InputOption(
                self::INPUT_KEY_BATCH_SIZE,
                'b',
                InputOption::VALUE_OPTIONAL,
                'Size of products to be extracted for relinking per one request',
                500
            )
        ]);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Zend_Db_Statement_Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->isModuleEnabled($this->connectorConfig, $output)) {
            return \Symfony\Component\Console\Command\Command::FAILURE;
        }
        $this->logger->addInfo('Start of relink process');
        $this->logger->addInfo('~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~');
        $isDryRun = false;
        if ($input->getArgument(self::INPUT_KEY_DRY_RUN)) {
            $isDryRun = true;
        }
        $isSKUCustomField = $input->getOption(self::INPUT_KEY_FIELD_CUSTOM);
        $startId = $input->getOption(self::INPUT_KEY_START_PRODUCT_ID);
        $batchSize = $input->getOption(self::INPUT_KEY_BATCH_SIZE);
        $netsuiteIdSearchField = $input->getOption(self::INPUT_KEY_NETSUITE_SKU_FIELD);
        $this->setAdminAppArea();

        $this->logger->addInfo('Parameters to start:');
        $this->logger->addInfo('~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~');
        $this->logger->addInfo(sprintf('Dry mode: %s ', $isDryRun ? 'yes' : 'no'));
        $this->logger->addInfo(sprintf('SKU in custom field: %s ', $isSKUCustomField ? 'yes' : 'no'));
        $this->logger->addInfo(sprintf('Product starting ID: %s ', $startId));
        $this->logger->addInfo(sprintf('NetSuite SKU field: %s ', $netsuiteIdSearchField));
        $this->logger->addInfo(sprintf('Batch size: %s', $batchSize));
        $this->logger->addInfo('~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~');

        $this->relinkProcessor->process(
            $isDryRun,
            (bool)$isSKUCustomField,
            $netsuiteIdSearchField,
            (int)$batchSize,
            (int)$startId
        );
        $this->logger->addInfo('Finish of the relink process.');

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }
}
