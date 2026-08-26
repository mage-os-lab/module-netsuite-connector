<?php

use Magento\TestFramework\Helper\Bootstrap;
use MageOS\NetSuiteConnector\Core\Test\Integration\Helper\NetSuiteFixtureCreator;
use MageOS\NetSuiteConnector\Core\Test\Integration\Model\NSRecordBuilder;

$simpleProductSku = 'simple-sku-test-tiered-price';
$simpleProductName = 'Simple Product test tiered price';
$simpleProductDesription = 'Simple Product tiered price description';
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
        // base price (in netsuite)
        '1' => [0, 20],
        // Logged in customer
        '5' => [0, 100],
        // Logged in wholesale customer
        '6' => [0, 50],
    ])
    ->build();

$fixtureCreator = $objectManager->get(NetSuiteFixtureCreator::class);

$refs = $fixtureCreator
    ->queueFixture(
        $product
    )->queueFixture(
        NSRecordBuilder::inventoryAdjustment($simpleProductSku, 1)
    );
