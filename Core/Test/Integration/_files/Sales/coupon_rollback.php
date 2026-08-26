<?php

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('adminhtml');

$registry = $objectManager->get('Magento\Framework\Registry');
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = $objectManager->create(\Magento\Framework\Api\SearchCriteriaBuilder::class);
$searchCriteria = $searchCriteriaBuilder->addFilter('name', 'custom_discount', 'eq')->create();

/** @var \Magento\SalesRule\Api\RuleRepositoryInterface $ruleRepository */
$ruleRepository = $objectManager->create(\Magento\SalesRule\Api\RuleRepositoryInterface::class);
$rules = $ruleRepository->getList($searchCriteria);
$rules = $rules->getItems();

foreach ($rules as $rule) {
    $ruleRepository->deleteById($rule->getRuleId());
}
