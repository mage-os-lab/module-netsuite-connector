<?php

use Magento\TestFramework\Helper\Bootstrap;
use MageOS\NetSuiteConnector\Core\Test\Integration\Helper\NetSuiteFixtureCreator;
use MageOS\NetSuiteConnector\Core\Test\Integration\Model\NSRecordBuilder;

$objectManager = Bootstrap::getObjectManager();

$fixtureCreator = $objectManager->get(NetSuiteFixtureCreator::class);

$configurableProduct = NSRecordBuilder::aRecord(\NetSuite\Classes\InventoryItem::class)
    ->withExternalId('Configurable-SKU-001')
    ->withTaxSchedule()
    ->withMatrixType(\NetSuite\Classes\ItemMatrixType::_parent)
    ->withStoreDisplayName('Configurable Product')
    ->withItemId('Configurable-SKU-001')
    ->build();

$refs = $fixtureCreator
    ->queueFixture(
        $configurableProduct
    )
    ->queueFixture(
        NSRecordBuilder::matrixChild(
            'Configurable-Simple-SKU-001',
            'Configurable-SKU-001',
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
            'Configurable-Simple-SKU-002',
            'Configurable-SKU-001',
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
            'Configurable-Simple-SKU-003',
            'Configurable-SKU-001',
            ['custitem18'=>'6'],
            [
                '5' => [0, 150],
                // wholesale
                '6' => [0, 99.02],
            ]
        )
    )->queueFixture(
        NSRecordBuilder::inventoryAdjustment('Configurable-Simple-SKU-001', 1000)
    )->queueFixture(
        NSRecordBuilder::inventoryAdjustment('Configurable-Simple-SKU-002', 1000)
    )->queueFixture(
        NSRecordBuilder::inventoryAdjustment('Configurable-Simple-SKU-003', 1000)
    )
;
