<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

require __DIR__ . '/customer_rollback.php';

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
/** @var $repository \Magento\Customer\Api\CustomerRepositoryInterface */
$repository = $objectManager->create('Magento\Customer\Api\CustomerRepositoryInterface');
$customer = $objectManager->create('Magento\Customer\Model\Customer');

/** @var Magento\Customer\Model\Customer $customer */
$customer->setWebsiteId(1)
    ->setId(1)
    ->setEmail('customer@null.com')
    ->setPassword('password')
    ->setGroupId(1)
    ->setStoreId(1)
    ->setIsActive(1)
    ->setPrefix('Mr.')
    ->setFirstname('John')
    ->setMiddlename('A')
    ->setLastname('Smith')
    ->setSuffix('Esq.')
    ->setDefaultBilling(1)
    ->setDefaultShipping(1)
    ->setTaxvat('12')
    ->setGender(0);


// $customer->isObjectNew(true);
$customer->save();

$customer = $repository->getById($customer->getId());
$customer->setAddresses([]);
$customer->setCustomAttribute('netsuite_internal_id', '2');
$repository->save($customer);

//$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
//
//$url = \Magento\Framework\App\ObjectManager::getInstance();
//
//$storeManager = $url->get('\Magento\Store\Model\StoreManagerInterface');
//
//$state = $objectManager->get('\Magento\Framework\App\State');
//
//$state->setAreaCode('frontend');
//
//// Customer Factory to Create Customer
//
//$customerFactory = $objectManager->get('\Magento\Customer\Model\CustomerFactory');
//
//$websiteId = $storeManager->getWebsite()->getWebsiteId();
//
//$store = $storeManager->getStore();  // Get Store ID
//
//$storeId = $store->getStoreId();
//
//// Instantiate object (this is the most important part)
//
//$customer = $customerFactory->create();
//
//$customer->setWebsiteId($websiteId);
//
//$customer->setEmail("hello@example.com");
//
//$customer->setFirstname("John");
//
//$customer->setLastname("Doe");
//
//$customer->setPassword('152452652');
//
//$customer->save();
//
//echo 'Create customer successfully'.$customer->getId();
