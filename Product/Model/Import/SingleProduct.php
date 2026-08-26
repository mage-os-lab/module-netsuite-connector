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

namespace MageOS\NetSuiteConnector\Product\Model\Import;

use NetSuite\Classes\ItemSearchBasic;
use NetSuite\Classes\RecordRef;
use NetSuite\Classes\RecordType;
use NetSuite\Classes\SearchMultiSelectField;
use NetSuite\Classes\SearchMultiSelectFieldOperator;
use NetSuite\Classes\SearchRequest;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Search;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Business logic of importing single product for ImportSingleProduct CLI command
 *
 * The class still has high coupling between objects (value = 19) so its manually suppressed for now.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SingleProduct
{
    /**
     * @var
     */
    protected $netsuiteService;
    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management
     */
    private $serviceManagement;
    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\ImportQueueManager
     */
    private $importQueue;
    /**
     * @var \MageOS\NetSuiteConnector\Product\Model\Process\Import\Item
     */
    private $importProcessModel;
    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    private $filterBuilder;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepositoryInterface;
    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\Logger\Logger
     */
    private $logger;

    public function __construct(
        \MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger,
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement,
        \MageOS\NetSuiteConnector\Core\Model\ImportQueueManager $importQueue,
        \MageOS\NetSuiteConnector\Product\Model\Process\Import\Item $importProcessModel,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface
    ) {
        $this->serviceManagement = $serviceManagement;
        $this->importQueue = $importQueue;
        $this->importProcessModel = $importProcessModel;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->logger = $logger;
    }

    /**
     * @param array $netsuiteInternalIds
     * @param InputInterface $input
     * @return mixed
     * @throws \MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException
     * @throws \MageOS\NetSuiteConnector\Core\Exception\NetSuiteRuntimeException
     */
    public function importProducts(array $netsuiteInternalIds, InputInterface $input): void
    {
        $netsuiteRequest = $this->initNetsuiteRequest($netsuiteInternalIds);

        $searcher = new Search(
            $netsuiteRequest,
            $this->serviceManagement
        );

        foreach ($searcher->allInBatches() as $records) {
            $this->importProcessModel->prefetchProducts($records);

            if (empty($records)) {
                continue;
            }

            usort($records, [$this, 'sortByInventoryItemType']);

            foreach ($records as $record) {
                /** @var \NetSuite\Classes\InventoryItem $record */
                try {
                    $rows = $this->importProcessModel->process($record);
                    $this->importQueue->import($rows);
                } catch (\Exception $ex) {
                    $this->logger->addInfo($ex);
                }
            }
        }

        try {
            $this->importQueue->commit();
        } catch (\Exception $ex) {
            $this->logger->addInfo($ex);
        }
    }

    protected function initNetsuiteRequest($netsuiteInternalIds): SearchRequest
    {
        $tranSearchBasic = new ItemSearchBasic();
        $tranSearchBasic->internalId = new SearchMultiSelectField();
        $tranSearchBasic->internalId->operator = SearchMultiSelectFieldOperator::anyOf;

        foreach ($netsuiteInternalIds as $netsuiteInternalId) {
            $internalIdField = new RecordRef();
            $internalIdField->internalId = $netsuiteInternalId;
            $internalIdField->type = RecordType::inventoryItem;

            $tranSearchBasic->internalId->searchValue[] = $internalIdField;
        }

        $searchRequest = new SearchRequest();
        $searchRequest->searchRecord = $tranSearchBasic;

        return $searchRequest;
    }

    public function getConfigurableProducts(\Magento\Catalog\Api\Data\ProductInterface $productInterface)
    {
        if ($productInterface->getTypeId() != \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            return [];
        }

        $ids = $productInterface->getExtensionAttributes()->getConfigurableProductLinks();

        $filters = [];
        $filters[] = $this->filterBuilder
            ->setField('entity_id')
            ->setConditionType('in')
            ->setValue($ids)
            ->create();

        $this->searchCriteriaBuilder->addFilters($filters);

        $searchCriteria = $this->searchCriteriaBuilder->create();
        return $this->productRepositoryInterface->getList($searchCriteria)->getItems();
    }

    /**
     * @param $a
     * @param $b
     * @return int
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function sortByInventoryItemType($a, $b)
    {
        if (($a->matrixType ?? null) == '_parent') {
            return -1;
        } else {
            return 0;
        }
    }
}
