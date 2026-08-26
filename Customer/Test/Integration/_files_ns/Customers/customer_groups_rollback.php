<?php

use Magento\Customer\Api\Data\GroupExtensionFactory;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

$groupRepository = $objectManager->get(GroupRepositoryInterface::class);

$registry = $objectManager->get(\Magento\Framework\Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

//$groupRepository->deleteById(10);
//$groupRepository->deleteById(11);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
