<?php

use Magento\Customer\Model\Address;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

$registry = Bootstrap::getObjectManager()->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var Address $customerAddress */
$customerAddress = Bootstrap::getObjectManager()
    ->create(Address::class);

$addressIds = [1,2];
foreach ($addressIds as $id) {
    $customerAddress->load($id);
    if ($customerAddress->getId()) {
        $customerAddress->delete();
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
