<?php

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\NetSuiteConnector\Core\Test\Integration\Helper\NetSuiteFixtureCreator;
use MageOS\NetSuiteConnector\Core\Test\Integration\Model\NSRecordBuilder;

$objectManager = Bootstrap::getObjectManager();
$fixtureCreator = $objectManager->get(NetSuiteFixtureCreator::class);

$discountItem = NSRecordBuilder::aRecord(\NetSuite\Classes\InventoryItem::class)
    ->withItemId('DI001')
    ->withTaxSchedule()
    ->withExternalId('DI001')
    ->pricing([
        '5' => [0, 10],
    ])
    ->build();

$refs = $fixtureCreator->create(
    [$discountItem]
);

if (!empty($refs)) {
    $discountItemInternalId = current($refs)->internalId;

    $configWriter = $objectManager->get(WriterInterface::class);
    $configWriter->save(
        'mageos_netsuite/orders/discount_item_id',
        $discountItemInternalId
    );

    // cleanup config cache
    $objectManager->get(\Magento\Framework\App\Config::class)->clean();

}
