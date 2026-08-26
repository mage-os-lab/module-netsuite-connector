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

namespace MageOS\NetSuiteConnector\Product\Command\Util;

use MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command that imports single (or a CSV list) product. It processes either SKUs or NetSuite Internal IDs
 *
 * The class still has high coupling between objects (value = 15) so its manually suppressed for now.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ImportSingleProduct extends \MageOS\NetSuiteConnector\Core\Command\AbstractNSCommand
{
    private const INPUT_KEY_ID = 'id';
    private const INPUT_KEY_DELETE = 'delete-existing';
    private const INPUT_KEY_DEBUG = 'debug';
    private const INPUT_KEY_SKU = 'sku';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var ConnectorConfig
     */
    private $connectorConfig;
    /**
     * @var \MageOS\NetSuiteConnector\Product\Model\Import\SingleProduct
     */
    private $importSingleProduct;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var \MageOS\NetSuiteConnector\Product\Model\ResourceModel\Repository
     */
    private $netsuiteProductRepository;

    /**
     * @param \MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger
     * @param \MageOS\NetSuiteConnector\Product\Model\Import\SingleProduct $importSingleProduct
     * @param ConnectorConfig $connectorConfig
     * @param \MageOS\NetSuiteConnector\Product\Model\ResourceModel\Repository $netsuiteProductRepository
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Model\Context $context
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger,
        \MageOS\NetSuiteConnector\Product\Model\Import\SingleProduct $importSingleProduct,
        \MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig $connectorConfig,
        \MageOS\NetSuiteConnector\Product\Model\ResourceModel\Repository $netsuiteProductRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Model\Context $context
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->connectorConfig = $connectorConfig;
        $this->importSingleProduct = $importSingleProduct;
        $this->productRepository = $productRepository;

        parent::__construct($context, $logger);
        $this->netsuiteProductRepository = $netsuiteProductRepository;
    }

    protected function configure()
    {
        $this->setName('netsuite:utils:importsingleproduct')->setDescription('Manually imports a single product');
        $this->setDefinition([
            new InputOption(
                self::INPUT_KEY_ID,
                null,
                InputOption::VALUE_REQUIRED,
                'The NetSuite internal id for the product to be imported (or comma-separated list)'
            ),
            new InputOption(
                self::INPUT_KEY_DELETE,
                null,
                InputOption::VALUE_NONE,
                'If this option is present, the product will be deleted and re-created'
            ),
            new InputOption(self::INPUT_KEY_DEBUG, null, InputOption::VALUE_OPTIONAL, 'Verbose logging'),
            new InputOption(self::INPUT_KEY_SKU, null, InputOption::VALUE_OPTIONAL, 'Product SKU')
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->isModuleEnabled($this->connectorConfig, $output)) {
            return \Symfony\Component\Console\Command\Command::FAILURE;
        }

        try {
            $this->executeReal($input, $output);
        } catch (\Exception $e) {
            $this->logger->addCritical($e);
            return \Symfony\Component\Console\Command\Command::FAILURE;
        }

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }

    protected function executeReal(InputInterface $input, OutputInterface $output)
    {
        $this->setAdminAppArea();

        $netsuiteInternalIdList = $input->getOption(self::INPUT_KEY_ID);
        $skuList = $input->getOption(self::INPUT_KEY_SKU);

        if ($skuList) {
            $netsuiteInternalIds = $this->processBySkus($skuList, $input);
        } else {
            if (!$netsuiteInternalIdList) {
                $this->logger->addError("id must be specified");
                return;
            }
            $netsuiteInternalIds = $this->processByIds($netsuiteInternalIdList, $input);
        }

        $this->logger->addInfo("Fetching NS#: <info>" . implode(',', $netsuiteInternalIds));

        $output->writeln("Fetching NS#: <info>" . implode(',', $netsuiteInternalIds) . "</info>");
        $this->importSingleProduct->importProducts($netsuiteInternalIds, $input);
        $output->writeln('<info>Done</info>');

        $this->logger->addInfo("Done");
    }

    /**
     * @param $netsuiteInternalIdList
     * @param InputInterface $input
     * @return array
     * @throws \Magento\Framework\Exception\StateException
     */
    protected function processByIds($netsuiteInternalIdList, InputInterface $input): array
    {
        $netsuiteInternalIds = [];
        $netsuiteInternalIdsList = explode(',', $netsuiteInternalIdList);

        foreach ($netsuiteInternalIdsList as $netsuiteInternalId) {
            $products = $this->netsuiteProductRepository->loadProductsByNetSuiteId($netsuiteInternalId);
            if (count($products) == 0) {
                $netsuiteInternalIds[] = $netsuiteInternalId;
            } else {
                $product = array_shift($products);
                // phpcs:ignore
                $netsuiteInternalIds = array_merge(
                    $netsuiteInternalIds,
                    $this->processByProducts($product, $input)
                );
            }
        }

        return $netsuiteInternalIds;
    }

    /**
     * @param $skuList
     * @param InputInterface $input
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    protected function processBySkus($skuList, InputInterface $input): array
    {
        $skus = explode(',', $skuList);
        $netsuiteInternalIds = [];
        foreach ($skus as $sku) {
            $product = $this->productRepository->get($sku);
            // phpcs:ignore
            $netsuiteInternalIds = array_merge(
                $netsuiteInternalIds,
                $this->processByProducts($product, $input)
            );
        }

        return $netsuiteInternalIds;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param InputInterface $input
     * @param array $netsuiteInternalIds
     * @return array
     * @throws \Magento\Framework\Exception\StateException
     */
    protected function processByProducts(
        \Magento\Catalog\Api\Data\ProductInterface $product,
        InputInterface $input
    ): array {
        $netsuiteInternalIds = [];

        $associatedProducts = [];
        if ($product && $product->getId()) {
            $associatedProducts = $this->importSingleProduct->getConfigurableProducts($product);
        }

        if ($input->getOption(self::INPUT_KEY_DELETE)) {
            foreach ($associatedProducts as $associatedProduct) {
                $this->productRepository->delete($associatedProduct);
            }

            if ($product) {
                $this->productRepository->delete($product);
            }
        } else {
            foreach ($associatedProducts as $associatedProduct) {
                $netsuiteInternalIds[] = $associatedProduct->getNetsuiteInternalId();
            }
        }

        $netsuiteInternalIds[] = $product->getNetsuiteInternalId();

        return $netsuiteInternalIds;
    }
}
