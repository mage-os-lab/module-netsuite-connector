<?php

use Magento\TestFramework\Helper\Bootstrap;
use MageOS\NetSuiteConnector\Core\Test\Integration\Helper\NetSuiteFixtureCreator;
use MageOS\NetSuiteConnector\Core\Test\Integration\Model\NSRecordBuilder;

$objectManager = Bootstrap::getObjectManager();

$fixtureCreator = $objectManager->get(NetSuiteFixtureCreator::class);

for ($batch = 1; $batch <= 51; $batch++) {
    $simpleProductSku = 'simple-product-sku-test'.$batch;
    $simpleProductName = 'Simple Product test name'.$batch;
    $simpleProductDesription = 'Simple Product description'.$batch;
    $simpleProductManufacturerCountry = "_bolivia";



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



//    $refs = $fixtureCreator
//        ->queueFixture(
//            $product
//        )->queueFixture(
//            NSRecordBuilder::inventoryAdjustment($simpleProductSku, 1)
//        );

    $fixtureCreator
        ->queueFixture(
            $product
        )->queueFixture(
            NSRecordBuilder::inventoryAdjustment($simpleProductSku, 1)
        );
}

$refs = $fixtureCreator;
