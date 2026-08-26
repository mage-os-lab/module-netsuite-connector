<?php

\Magento\TestFramework\Helper\Bootstrap::getInstance()->reinitialize();

/** @var \Magento\TestFramework\ObjectManager $objectManager */
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('adminhtml');

$registry = $objectManager->get('Magento\Framework\Registry');
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);


// Delete Tax Rule
$searchCriteriaBuilder = $objectManager->create(\Magento\Framework\Api\SearchCriteriaBuilder::class);
$searchCriteria = $searchCriteriaBuilder->addFilter('code', 'Test123', 'eq')->create();
$taxRuleRepository = $objectManager->create(\Magento\Tax\Model\TaxRuleRepository::class);
$taxRules = $taxRuleRepository->getList($searchCriteria);
$taxRules = $taxRules->getItems();
foreach ($taxRules as $taxRule) {
    $taxRuleRepository->deleteById($taxRule->getId());
}


// Delete Tax Rate
$searchCriteriaBuilder = $objectManager->create(\Magento\Framework\Api\SearchCriteriaBuilder::class);
$searchCriteria = $searchCriteriaBuilder->addFilter('code', 'TestRate123', 'eq')->create();
$taxRateRepository = $objectManager->create(\Magento\Tax\Model\Calculation\RateRepository::class);
$taxRates = $taxRateRepository->getList($searchCriteria);
$taxRates = $taxRates->getItems();
foreach ($taxRates as $taxRate) {
    $taxRateRepository->deleteById($taxRate->getId());
}
