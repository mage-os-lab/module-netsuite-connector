<?php

require_once __DIR__ . '/ns_constants.php';

use Magento\Customer\Api\Data\GroupExtensionFactory;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

$groupRepository = $objectManager->get(GroupRepositoryInterface::class);

try {
    $group = $objectManager->create(GroupInterface::class);
    $group->setId(1);
    $group->setCode('GeneralGroup');
    $group->setTaxClassId(TAX_CLASS_ID);

    $groupExtension = $objectManager->create(GroupExtensionFactory::class);
    $extAttr = $groupExtension->create();
    $extAttr->setNetsuiteInternalId(ONLINE_PRICE_LEVEL);
    $group->setExtensionAttributes($extAttr);

    $groupRepository->save($group);

    $group->setId(10);
    $groupRepository->save($group);
} catch (\Exception $e) {

}

try {
    $group = $objectManager->create(GroupInterface::class);
    $group->setId(2);
    $group->setCode('WholeSaleGroup');
    $group->setTaxClassId(TAX_CLASS_ID);

    $groupExtension = $objectManager->create(GroupExtensionFactory::class);
    $extAttr = $groupExtension->create();
    $extAttr->setNetsuiteInternalId(WHOLESALE_PRICE_LEVEL);
    $group->setExtensionAttributes($extAttr);

    $groupRepository->save($group);

    $group->setId(11);
    $groupRepository->save($group);

} catch (\Exception $e) {

}

/**
 * @param $code
 * @return bool
 * @throws \Magento\Framework\Exception\LocalizedException
 */
function isCustomerGroupExistsNetsuite($id): bool
{
    $objectManager = Bootstrap::getObjectManager();
    $groupRepository = $objectManager->get(GroupRepositoryInterface::class);

    try {
        $groupRepository->getById($id);
        return true;
    } catch (\Exception $e) {
        return false;
    }
}
