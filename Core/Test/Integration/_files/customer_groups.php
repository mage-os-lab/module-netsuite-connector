<?php

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var \Magento\Customer\Api\GroupRepositoryInterface $groupRepository */
$groupRepository = $objectManager->get(\Magento\Customer\Api\GroupRepositoryInterface::class);

if (!function_exists('isCustomerGroupExists')) {
    function isCustomerGroupExists($nsId): bool
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

        $groupCount = $groupRepository->getList($searchCriteria)->getTotalCount();

        return $groupCount > 0;
    }
}



if (!isCustomerGroupExists(6)) {
    /** @var \Magento\Customer\Api\Data\GroupInterface $group */
    $group = $objectManager->create(\Magento\Customer\Api\Data\GroupInterface::class);
    $group->setCode('Group 6');
    $group->setTaxClassId(3);

    /** @var \Magento\Customer\Api\Data\GroupExtensionFactory $extAttr */
    $groupExtension = $objectManager->create(\Magento\Customer\Api\Data\GroupExtensionFactory::class);
    $extAttr = $groupExtension->create();
    $extAttr->setNetsuiteInternalId(6);
    $group->setExtensionAttributes($extAttr);

    $groupRepository->save($group);
}

if (!isCustomerGroupExists(4)) {
    /** @var \Magento\Customer\Api\Data\GroupInterface $group */
    $group = $objectManager->create(\Magento\Customer\Api\Data\GroupInterface::class);
    $group->setCode('Group 4');
    $group->setTaxClassId(3);

    /** @var \Magento\Customer\Api\Data\GroupExtensionFactory $extAttr */
    $groupExtension = $objectManager->create(\Magento\Customer\Api\Data\GroupExtensionFactory::class);
    $extAttr = $groupExtension->create();
    $extAttr->setNetsuiteInternalId(4);
    $group->setExtensionAttributes($extAttr);

    $groupRepository->save($group);
}

if (!isCustomerGroupExists(5)) {
    /** @var \Magento\Customer\Api\Data\GroupInterface $group */
    $group = $objectManager->create(\Magento\Customer\Api\Data\GroupInterface::class);
    $group->setCode('Group 5');
    $group->setTaxClassId(3);

    /** @var \Magento\Customer\Api\Data\GroupExtensionFactory $extAttr */
    $groupExtension = $objectManager->create(\Magento\Customer\Api\Data\GroupExtensionFactory::class);
    $extAttr = $groupExtension->create();
    $extAttr->setNetsuiteInternalId(5);
    $group->setExtensionAttributes($extAttr);

    $groupRepository->save($group);
}
