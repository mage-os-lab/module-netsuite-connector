<?php


\Magento\TestFramework\Helper\Bootstrap::getInstance()->reinitialize();

/** @var \Magento\TestFramework\ObjectManager $objectManager */
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('adminhtml');

$registry = $objectManager->get('Magento\Framework\Registry');
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);



// Create Tax rate
/** @var \Magento\Tax\Model\Calculation\Rate::class $fixtureTaxRate */
$fixtureTaxRate = $objectManager->create(\Magento\Tax\Model\Calculation\Rate::class);
$fixtureTaxRate->setCode("TestRate123");
$fixtureTaxRate->setTaxPostcode("*");
$fixtureTaxRate->setTaxRegionId(1);
$fixtureTaxRate->setTaxCountryId('US');
$fixtureTaxRate->setRate(20);
try {
    $fixtureTaxRate->save();
} catch (\Magento\Framework\Exception\AlreadyExistsException $ex) {

}



// Create Tax Rule
$searchCriteriaBuilder = $objectManager->create(\Magento\Framework\Api\SearchCriteriaBuilder::class);
$searchCriteria = $searchCriteriaBuilder->addFilter('code', 'TestRate123', 'eq')->create();
$taxRateRepository = $objectManager->create(\Magento\Tax\Model\Calculation\RateRepository::class);
$taxRates = $taxRateRepository->getList($searchCriteria);
$taxRates = $taxRates->getItems();

$taxRateId = 0;
foreach ($taxRates as $taxRate) {
    $taxRateId = $taxRate->getId();
}
$fixtureTaxRule = $objectManager->create(\Magento\Tax\Model\Calculation\Rule::class);
$fixtureTaxRule->setCode("Test123");
$fixtureTaxRule->setPriority(0);
$fixtureTaxRule->setCustomerTaxClassIds([3]);
$fixtureTaxRule->setProductTaxClassIds([2]);
$fixtureTaxRule->setTaxRateIds([$taxRateId]);

try {
    $fixtureTaxRule->save();
} catch (\Magento\Framework\Exception\AlreadyExistsException $ex) {

}
