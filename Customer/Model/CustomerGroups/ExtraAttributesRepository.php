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
 *
 */

namespace MageOS\NetSuiteConnector\Customer\Model\CustomerGroups;

use Exception;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SortOrder;
use MageOS\NetSuiteConnector\Core\Exception\ConnectorRuntimeException;
use MageOS\NetSuiteConnector\Customer\Api\CustomerGroups\ExtraAttributesRepositoryInterface;
use MageOS\NetSuiteConnector\Customer\Model\CustomerGroups\ResourceModel\ExtraAttributes\CollectionFactory;

class ExtraAttributesRepository implements ExtraAttributesRepositoryInterface
{
    /**
     * @var ExtraAttributesFactory
     */
    protected $objectFactory;
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;
    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * @var SearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    public function __construct(
        ExtraAttributesFactory $objectFactory,
        CollectionFactory $collectionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SearchResultsInterfaceFactory $searchResultsFactory
    ) {
        $this->objectFactory        = $objectFactory;
        $this->collectionFactory    = $collectionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    /**
     * @param ExtraAttributesInterface $object
     * @return ExtraAttributesInterface
     * @throws ConnectorRuntimeException
     */
    public function save(ExtraAttributesInterface $object)
    {
        try {
            $object->save();
        } catch (Exception $e) {
            throw new ConnectorRuntimeException($e->getMessage());
        }

        return $object;
    }

    /**
     * @param $id
     * @return ExtraAttributesInterface
     */
    public function getById($id)
    {
        $object = $this->objectFactory->create();
        $object->load($id);
        return $object;
    }

    /**
     * @param ExtraAttributesInterface $object
     * @return bool
     * @throws ConnectorRuntimeException
     */
    public function delete(ExtraAttributesInterface $object)
    {
        try {
            $object->delete();
        } catch (Exception $exception) {
            throw new ConnectorRuntimeException($exception->getMessage());
        }
        return true;
    }

    /**
     * @param $id
     * @return bool
     * @throws ConnectorRuntimeException
     */
    public function deleteById($id)
    {
        return $this->delete($this->getById($id));
    }

    /**
     * @param $internalId
     * @return ExtraAttributesInterface
     */
    public function getByNetsuiteInternalId($internalId)
    {
        $search_criteria = $this->searchCriteriaBuilder
            ->addFilter('netsuite_internal_id', $internalId, 'eq')
            ->create();

        $filter = $this->getList($search_criteria);

        $items = $filter->getItems();

        if (count($items) > 0) {
            return $items[0];
        } else {
            return null;
        }
    }

    /**
     * @param SearchCriteriaInterface $criteria
     * @return mixed
     */
    public function getList(SearchCriteriaInterface $criteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        $collection = $this->collectionFactory->create();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            $fields = [];
            $conditions = [];
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ? $filter->getConditionType() : 'eq';
                $fields[] = $filter->getField();
                $conditions[] = [$condition => $filter->getValue()];
            }
            if ($fields) {
                $collection->addFieldToFilter($fields, $conditions);
            }
        }
        $searchResults->setTotalCount($collection->getSize());
        $sortOrders = $criteria->getSortOrders();
        if ($sortOrders) {
            /** @var SortOrder $sortOrder */
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }
        }
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());
        $objects = [];
        foreach ($collection as $objectModel) {
            $objects[] = $objectModel;
        }
        $searchResults->setItems($objects);
        return $searchResults;
    }
}
