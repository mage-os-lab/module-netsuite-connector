<?php

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Address;
use Magento\Customer\Model\AddressRegistry;
use Magento\Customer\Model\CustomerRegistry;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Customer\Api\CustomerRepositoryInterface;

if (!function_exists('createAddress')) {
    /**
     * @param $customerId
     * @param $data
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    function createAddress($customerId, $data)
    {
        $objectManager = Bootstrap::getObjectManager();

        $customerAddress = $objectManager->create(Address::class);
        $customerRegistry = $objectManager->get(CustomerRegistry::class);
        $customerAddress->isObjectNew(true);
        $customerAddress->setData(
            $data
        );
        $customerAddress->save();

        $addressRepository = $objectManager->get(AddressRepositoryInterface::class);
        $customerAddress = $addressRepository->getById($data['entity_id']);
        $customerAddress->setCustomerId($customerId);
        $customerAddress = $addressRepository->save($customerAddress);
        $customerRegistry->remove($customerAddress->getCustomerId());

        $addressRegistry = $objectManager->get(AddressRegistry::class);
        $addressRegistry->remove($customerAddress->getId());
    }
}

$objectManager = Bootstrap::getObjectManager();

$customerRepository = $objectManager->get(CustomerRepositoryInterface::class);
$customer = $customerRepository->get('customer-general@rocketweb.com');

createAddress(
    $customer->getId(),
    [
        'entity_id' => 1,
        'attribute_set_id' => 2,
        'telephone' => 3468676,
        'postcode' => 75477,
        'country_id' => 'US',
        'city' => 'CityM',
        'company' => 'CompanyName',
        'street' => 'General str, 67',
        'lastname' => 'Smith',
        'firstname' => 'John',
        'parent_id' => $customer->getId(),
        'region_id' => 1,
    ]
);

createAddress(
    $customer->getId(),
    [
        'entity_id' => 2,
        'attribute_set_id' => 2,
        'telephone' => 3468677,
        'postcode' => 75478,
        'country_id' => 'US',
        'city' => 'CityM2',
        'company' => 'CompanyName2',
        'street' => 'General str, 68',
        'lastname' => 'Smith',
        'firstname' => 'Jenny',
        'parent_id' => $customer->getId(),
        'region_id' => 1,
    ]
);
