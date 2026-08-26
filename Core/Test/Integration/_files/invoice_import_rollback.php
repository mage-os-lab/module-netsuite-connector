<?php
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var \Magento\Framework\Registry $registry */
$registry = $objectManager->get(\Magento\Framework\Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var InvoiceRepositoryInterface $cmRepository */
$invoiceRepository = $objectManager->get(\Magento\Sales\Api\InvoiceRepositoryInterface::class);

/** @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = $objectManager->get(\Magento\Framework\Api\SearchCriteriaBuilder::class);

$searchCriteria = $searchCriteriaBuilder
    ->create();

$magentoInvoices = $invoiceRepository->getList($searchCriteria)->getItems();
foreach ($magentoInvoices as $magentoInvoice) {
    $invoiceRepository->delete($magentoInvoice);
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
