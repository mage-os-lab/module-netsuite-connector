<?php

use Magento\Customer\Api\CustomerRepositoryInterface;

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var \Magento\Framework\Registry $registry */
$registry = $objectManager->get(\Magento\Framework\Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

//$CustomerModel = $objectManager->create('Magento\Customer\Model\Customer');
//$CustomerModel->setWebsiteId(1); //Here 1 means Store ID
//$CustomerModel->loadByEmail('customer-general@rocketweb.com');
//$userId = $CustomerModel->getId();

$customer = $objectManager->create(CustomerRepositoryInterface::class)->get('customer-general@rocketweb.com');
$customer->setWebsiteId(1);

/** @var $repository \Magento\Customer\Api\CustomerRepositoryInterface */
$repository = $objectManager->create('Magento\Customer\Api\CustomerRepositoryInterface');

try {
    //$repository->deleteById($userId);
    $repository->delete($customer);
} catch (Magento\Framework\Exception\LocalizedException $localizedException) {
    echo "Could not find user\n";// phpcs:ignore
    echo $localizedException->getTraceAsString() . "\n";// phpcs:ignore
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
