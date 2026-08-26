<?php

use Magento\TestFramework\Helper\Bootstrap;
use MageOS\NetSuiteConnector\Core\Test\Integration\Helper\NetSuiteFixtureCreator;
use MageOS\NetSuiteConnector\Core\Test\Integration\Model\NSRecordBuilder;

$objectManager = Bootstrap::getObjectManager();

$product = NSRecordBuilder::aRecord(\NetSuite\Classes\InventoryItem::class)
    ->withItemId('Simple-SKU-002')
    ->withTaxSchedule()
    ->withExternalId('Simple-SKU-002')
    ->pricing([
        '5' => [0, 100],
        // wholesale
        '6' => [0, 50.99],
    ])
    ->build();

$fixtureCreator = $objectManager->get(NetSuiteFixtureCreator::class);

$refs = $fixtureCreator
    ->queueFixture(
        $product
    )->queueFixture(
        NSRecordBuilder::inventoryAdjustment('Simple-SKU-002', 1000)
    )->queueFixture(
        NSRecordBuilder::inventoryAdjustment('Simple-SKU-002', 1000, '5')
    );
