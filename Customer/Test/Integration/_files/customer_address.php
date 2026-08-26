<?php
/**
 * Customer address fixture with entity_id = 1
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var \Magento\Customer\Model\Address $customerAddress */
$customerAddress = $objectManager->create(\Magento\Customer\Model\Address::class);
/** @var \Magento\Customer\Model\CustomerRegistry $customerRegistry */
$customerRegistry = $objectManager->get(\Magento\Customer\Model\CustomerRegistry::class);
$customerAddress->isObjectNew(true);
$customerAddress->setData(
    [
        'entity_id' => 1,
        'attribute_set_id' => 2,
        'telephone' => 3468676,
        'postcode' => 75477,
        'country_id' => 'US',
        'city' => 'CityM',
        'company' => 'CompanyName',
        'street' => 'Green str, 67',
        'lastname' => 'Smith',
        'firstname' => 'John',
        'parent_id' => 1,
        'region_id' => 1,
    ]
);
$customerAddress->save();

/** @var \Magento\Customer\Model\AddressRegistry $addressRegistry */
$addressRegistry = $objectManager->get(\Magento\Customer\Model\AddressRegistry::class);
$addressRegistry->remove(1);
$customerRegistry->remove(1);

/** @var \Magento\Customer\Api\AddressRepositoryInterface $addressRepository */
$addressRepository = $objectManager->get(\Magento\Customer\Api\AddressRepositoryInterface::class);
$customerAddress = $addressRepository->getById(1);
$customerAddress->setCustomerId(1);
$customerAddress->setIsDefaultBilling(true);
$customerAddress->setIsDefaultShipping(true);
$customerAddress = $addressRepository->save($customerAddress);
$customerRegistry->remove($customerAddress->getCustomerId());
$addressRegistry->remove($customerAddress->getId());

/** @var \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryFixture */
$customerRepositoryFixture = $objectManager->get(\Magento\Customer\Api\CustomerRepositoryInterface::class);
$fixtureCustomer = $customerRepositoryFixture->getById(1);
$fixtureCustomer->setDefaultBilling((string)$customerAddress->getId());
$fixtureCustomer->setDefaultShipping((string)$customerAddress->getId());
$customerRepositoryFixture->save($fixtureCustomer);
$customerRegistry->remove(1);
$addressRegistry->remove($customerAddress->getId());
