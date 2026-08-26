<?php

use Magento\TestFramework\Helper\Bootstrap;
use MageOS\NetSuiteConnector\Core\Test\Integration\Helper\NetSuiteFixtureCreator;
use MageOS\NetSuiteConnector\Core\Test\Integration\Model\NSRecordBuilder;

$simpleProductSku = 'simple-product-sku-test';
$simpleProductName = 'Simple Product test name';
$simpleProductDesription = 'Simple Product description';
$simpleProductManufacturerCountry = "_bolivia";

$objectManager = Bootstrap::getObjectManager();

$product = NSRecordBuilder::aRecord(\NetSuite\Classes\InventoryItem::class)
    ->withItemId($simpleProductSku)
    ->withTaxSchedule()
    ->withExternalId($simpleProductSku)
    ->withStoreDisplayName($simpleProductName)
    ->withStoreDescription($simpleProductDesription)
    ->withCountryOfManufacture($simpleProductManufacturerCountry)
    ->pricing([
        '5' => [0, 10]
    ])
    ->build();

$fixtureCreator = $objectManager->get(NetSuiteFixtureCreator::class);

$refs = $fixtureCreator
    ->queueFixture(
        $product
    )->queueFixture(
        NSRecordBuilder::inventoryAdjustment($simpleProductSku, 100)
    );
