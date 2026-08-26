<?php

use Magento\TestFramework\Helper\Bootstrap;
use MageOS\NetSuiteConnector\Core\Test\Integration\Helper\NetSuiteFixtureCreator;

$objectManager = Bootstrap::getObjectManager();
$fixtureCreator = $objectManager->get(NetSuiteFixtureCreator::class)->createAll();
