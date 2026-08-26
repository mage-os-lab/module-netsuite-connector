<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */




//use Magento\Customer\Api\CustomerRepositoryInterface;
//use Magento\Customer\Model\Customer;
//use Magento\Framework\Registry;
//use Magento\TestFramework\Helper\Bootstrap;
//use MageOS\NetSuiteConnector\Core\Test\Integration\Helper\NetSuiteFixtureCreator;
//
//$objectManager = Bootstrap::getObjectManager();
//
//$repository = $objectManager->get(CustomerRepositoryInterface::class);
//$customer = $objectManager->create(Customer::class);
///** @var \MageOS\NetSuiteConnector\Core\Helper\Data $data */
//$data = $objectManager->get(\MageOS\NetSuiteConnector\Core\Helper\Data::class);
//
//$objectManager->get(Registry::class)->register('netsuite_force_send_customers', true);
//
//$customer->setWebsiteId(1)
//    ->setEmail('customer-general@rocketweb.com')
//    ->setPassword('password')
//    ->setGroupId(1)
//    ->setStoreId(1)
//    ->setIsActive(1)
//    ->setPrefix('Mr.')
//    ->setFirstname('John')
//    ->setMiddlename('A')
//    ->setLastname('Smith')
//    ->setSuffix('Esq.')
//    ->setDefaultBilling(1)
//    ->setDefaultShipping(2)
//    ->setTaxvat('12')
//    ->setGender(0);
//
//$customer->isObjectNew(true);
//$customer->save();
