<?php

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$configMap = [
    ['mageos_netsuite/products/default_visibility', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, '4'],
    ['mageos_netsuite/products/default_status', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, '1'],
    ['mageos_netsuite/products/default_website_ids', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, '1'],
    ['mageos_netsuite/products/field_map', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, null],
    ['mageos_netsuite/products/price_level_netsuite_id', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, '5'],
    ['mageos_netsuite/products/tier_price_customer_group', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, '32000'],
    ['mageos_netsuite/stock/stock_stored_at_location_level', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, '0'],
    ['mageos_netsuite/stock/qty_field_name', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, 'custitem_quantity'],
    [
        'mageos_netsuite/products/related_products_field',
        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        null,
        'custitem_related'
    ],
    ['mageos_netsuite/products/upsells_field', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, 'custitem_upsells'],
    ['mageos_netsuite/shipping_methods/netsuite_mapping', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, null],
    ['mageos_netsuite/payment_methods/netsuite_mapping', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, null],
    [
        'mageos_netsuite/shipping_methods/netsuite_default_shipping_id',
        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        null,
        '1'
    ]
];

/** @var \Magento\Framework\App\Config\Storage\WriterInterface $configWriter */
$configWriter = $objectManager->get(\Magento\Framework\App\Config\Storage\WriterInterface::class);

foreach ($configMap as $entry) {
    $configWriter->delete($entry[0]);
}

/** @var \Magento\Framework\Registry $registry */
$registry = $objectManager->get(\Magento\Framework\Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = $objectManager->get(\Magento\Framework\Api\SearchCriteriaBuilder::class);

/** @var ProductRepositoryInterface $rmaRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);

$searchCriteria = $searchCriteriaBuilder
    ->create();

$products = $productRepository->getList($searchCriteria)->getItems();
foreach ($products as $product) {
    $product->setCreatedIn('1');
    $productRepository->delete($product);
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
