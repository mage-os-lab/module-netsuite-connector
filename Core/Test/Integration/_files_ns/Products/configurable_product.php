<?php

use Magento\TestFramework\Helper\Bootstrap;
use MageOS\NetSuiteConnector\Core\Test\Integration\Helper\NetSuiteFixtureCreator;
use MageOS\NetSuiteConnector\Core\Test\Integration\Model\NSRecordBuilder;

$objectManager = Bootstrap::getObjectManager();

$fixtureCreator = $objectManager->get(NetSuiteFixtureCreator::class);

$configurableProductSku = 'configurable-sku-test';
$configurableProductName = 'Configurable Product test';
$configurableProductDesription = 'Configurable Product description';
$configurableProductManufacturerCountry = "_bolivia";

$configurableSimpleProductSku1 = 'configurable-simple-sku-test1';
$configurableSimpleProductSku2 = 'configurable-simple-sku-test2';
$configurableSimpleProductSku3 = 'configurable-simple-sku-test3';

$configurableProduct = NSRecordBuilder::aRecord(\NetSuite\Classes\InventoryItem::class)
    ->withItemId($configurableProductSku)
    ->withTaxSchedule()
    ->withExternalId($configurableProductSku)
    ->withMatrixType(\NetSuite\Classes\ItemMatrixType::_parent)
    ->withStoreDisplayName($configurableProductName)
    ->withStoreDescription($configurableProductDesription)
    ->withCountryOfManufacture($configurableProductManufacturerCountry)
    ->build();

$refs = $fixtureCreator
    ->queueFixture(
        $configurableProduct
    )
    ->queueFixture(
        NSRecordBuilder::matrixChild(
            $configurableSimpleProductSku1,
            $configurableProductSku,
            ['custitem18'=>'5'],
            [
                '5' => [0, 150],
                // wholesale
                '6' => [0, 99.02],
            ]
        )
    )
    ->queueFixture(
        NSRecordBuilder::matrixChild(
            $configurableSimpleProductSku2,
            $configurableProductSku,
            ['custitem18'=>'4'],
            [
                '5' => [0, 150],
                // wholesale
                '6' => [0, 99.02],
            ]
        )
    )
    ->queueFixture(
        NSRecordBuilder::matrixChild(
            $configurableSimpleProductSku3,
            $configurableProductSku,
            ['custitem18'=>'6'],
            [
                '5' => [0, 150],
                // wholesale
                '6' => [0, 99.02],
            ]
        )
    )->queueFixture(
        NSRecordBuilder::inventoryAdjustment($configurableSimpleProductSku1, 1000)
    )->queueFixture(
        NSRecordBuilder::inventoryAdjustment($configurableSimpleProductSku2, 1000)
    )->queueFixture(
        NSRecordBuilder::inventoryAdjustment($configurableSimpleProductSku3, 1000)
    )
;
