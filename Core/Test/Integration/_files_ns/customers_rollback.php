<?php

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/**
 * @param $email
 * @throws \Magento\Framework\Exception\LocalizedException
 */
if (!function_exists('deleteCustomer')) {
    function deleteCustomer($email)
    {
        $objectManager = Bootstrap::getObjectManager();

        $customerRepository
                  = $objectManager->get(CustomerRepositoryInterface::class);
        $customer = $customerRepository->get($email);

        if ($customer) {
            $customerRepository->delete($customer);
        }
    }
}

$registry = $objectManager->get(\Magento\Framework\Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

deleteCustomer('customer-general@rocketweb.com');
deleteCustomer('customer-wholesale@rocketweb.com');

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
