<?php

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Address;
use Magento\Customer\Model\AddressRegistry;
use Magento\Customer\Model\CustomerRegistry;
use Magento\TestFramework\Helper\Bootstrap;

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


createAddress(
    1,
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
        'parent_id' => 1,
        'region_id' => 1,
    ]
);

createAddress(
    1,
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
        'parent_id' => 1,
        'region_id' => 1,
    ]
);

createAddress(
    2,
    [
        'entity_id' => 3,
        'attribute_set_id' => 2,
        'telephone' => 3468676,
        'postcode' => 75477,
        'country_id' => 'US',
        'city' => 'CityM',
        'company' => 'CompanyName',
        'street' => 'Wholesale str, 67',
        'lastname' => 'Smith',
        'firstname' => 'John',
        'parent_id' => 1,
        'region_id' => 1,
    ]
);

createAddress(
    2,
    [
        'entity_id' => 4,
        'attribute_set_id' => 2,
        'telephone' => 3468677,
        'postcode' => 75478,
        'country_id' => 'US',
        'city' => 'CityM2',
        'company' => 'CompanyName2',
        'street' => 'Wholesale str, 68',
        'lastname' => 'Smith',
        'firstname' => 'Jenny',
        'parent_id' => 1,
        'region_id' => 1,
    ]
);
