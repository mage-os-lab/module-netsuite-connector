<?php
//phpcs:ignoreFile
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var \Magento\Framework\Registry $registry */
$registry = $objectManager->get(\Magento\Framework\Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$customerCollection = $objectManager->get(\Magento\Customer\Model\ResourceModel\Customer\Collection::class);

foreach ($customerCollection as $customer) {
    $customer->delete();
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
