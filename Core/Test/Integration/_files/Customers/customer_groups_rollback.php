<?php

/**
 * @throws \Magento\Framework\Exception\LocalizedException
 */
function deleteCustomerGroupByNetsuiteId($nsId)
{
    $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

    /** @var \Magento\Customer\Api\GroupRepositoryInterface $groupRepository */
    $groupRepository = $objectManager->get(\Magento\Customer\Api\GroupRepositoryInterface::class);
    /** @var \Magento\Framework\Api\FilterBuilder $filterBuilder */
    $filterBuilder = $objectManager->create(\Magento\Framework\Api\FilterBuilder::class);
    /** @var \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCritBuilderFactory */
    $searchCritBuilderFactory = $objectManager->get(\Magento\Framework\Api\SearchCriteriaBuilderFactory::class);

    $filters = [];
    $filters[] = $filterBuilder
        ->setField('netsuite_internal_id')
        ->setConditionType('eq')
        ->setValue($nsId)
        ->create();

    $searchCriteriaBuilder = $searchCritBuilderFactory->create();
    $searchCriteriaBuilder->addFilters($filters);
    $searchCriteria = $searchCriteriaBuilder->create();

    /** @var \Magento\Customer\Api\Data\GroupSearchResultsInterface $customerGroups */
    $customerGroups = $groupRepository->getList($searchCriteria);

    if (count($customerGroups->getItems()) > 0) {
        foreach ($customerGroups->getItems() as $customerGroup) {
            $groupRepository->delete($customerGroup);
        }
    }
}

try {
    deleteCustomerGroupByNetsuiteId('1');
    deleteCustomerGroupByNetsuiteId('5');
    deleteCustomerGroupByNetsuiteId('6');
} catch (Magento\Framework\Exception\LocalizedException $localizedException) {
    echo "Customer Group error:\n";// phpcs:ignore
    echo $localizedException->getLogMessage()."\n";// phpcs:ignore
    echo $localizedException->getTraceAsString()."\n";// phpcs:ignore
}
