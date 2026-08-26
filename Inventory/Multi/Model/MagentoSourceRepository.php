<?php declare(strict_types=1);
/*
 *   RocketWeb
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
 *
 *
 */

namespace MageOS\NetSuiteConnector\Inventory\Multi\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\SourceInterface;
use NetSuite\Classes\Location;

/**
 * Class MagentoSourceRepository - class responsible for manipulating with magento sources
 */
class MagentoSourceRepository
{
    private \Magento\InventoryApi\Api\SourceRepositoryInterface $sourceRepository;
    private \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory;
    private \MageOS\NetSuiteConnector\Inventory\Multi\Model\Mapper\ToMagento\Location $locationMapper;
    private \Magento\InventoryApi\Api\Data\SourceInterfaceFactory $sourceInterfaceFactory;
    private array $sourcesCache = [];

    /**
     * MagentoSourceRepository constructor.
     * @param \Magento\InventoryApi\Api\SourceRepositoryInterface $sourceRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param Mapper\ToMagento\Location $locationMapper
     * @param \Magento\InventoryApi\Api\Data\SourceInterfaceFactory $sourceInterfaceFactory
     */
    public function __construct(
        \Magento\InventoryApi\Api\SourceRepositoryInterface $sourceRepository,
        \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        \MageOS\NetSuiteConnector\Inventory\Multi\Model\Mapper\ToMagento\Location $locationMapper,
        \Magento\InventoryApi\Api\Data\SourceInterfaceFactory $sourceInterfaceFactory
    ) {
        $this->sourceRepository = $sourceRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->locationMapper = $locationMapper;
        $this->sourceInterfaceFactory = $sourceInterfaceFactory;
    }

    /**
     * we look for inventory source first by netsuite_internal_id
     * if we dont found we search by source_code(as primary key)
     * if no source found return null
     *
     * cache is used for multiple requests
     * @param int $netSuiteId
     * @param string|null $sourceCode
     * @return SourceInterface|null
     */
    public function getSourceByNetSuiteData(int $netSuiteId, ?string $sourceCode): ?SourceInterface
    {
        if (isset($this->sourcesCache[$netSuiteId])) {
            return $this->sourcesCache[$netSuiteId];
        }
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder
            ->addFilter('netsuite_internal_id', $netSuiteId, 'eq')
            ->create();

        $locations = $this->sourceRepository->getList($searchCriteria)->getItems();
        $this->sourcesCache[$netSuiteId] = empty($locations) ? null : array_shift($locations);
        if (empty($this->sourcesCache[$netSuiteId]) && null !== $sourceCode) {
            try {
                $this->sourcesCache[$netSuiteId] = $this->sourceRepository->get($sourceCode);
            //phpcs:disable
            } catch (NoSuchEntityException $e) {
                //if no source found with the source code from netsuite, proceed to creation of new source.
            }
            //phpcs:enable
        }
        return $this->sourcesCache[$netSuiteId];
    }

    /**
     * method updates magento inventory source with data from netsuite location
     * we do not update source_code if it was set before
     * @param Location $netsuiteLocation
     * @param SourceInterface $magentoSource
     * @return SourceInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Validation\ValidationException
     */
    public function update(Location $netsuiteLocation, SourceInterface $magentoSource): SourceInterface
    {
        $this->locationMapper->mapToMagento($magentoSource, $netsuiteLocation);
        $this->sourceRepository->save($magentoSource);
        return $magentoSource;
    }
}
