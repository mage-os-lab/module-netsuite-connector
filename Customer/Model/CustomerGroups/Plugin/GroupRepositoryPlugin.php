<?php
/**
 * RocketWeb
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category  RocketWeb
 * @package   MageOS_NetSuiteConnector
 * @copyright Copyright (c) 2026 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    Rocket Web Inc.
 *
 *
 */

namespace MageOS\NetSuiteConnector\Customer\Model\CustomerGroups\Plugin;

use Exception;
use Magento\Customer\Api\Data\GroupExtensionFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use MageOS\NetSuiteConnector\Customer\Model\CustomerGroups\ExtraAttributesRepository;

/**
 * Class GroupRepositoryPlugin
 */
class GroupRepositoryPlugin
{
    /** @var GroupExtensionFactory */
    private $groupExtensionFactory;
    /** @var ExtraAttributesRepository */
    private $extraAttributesRepository;

    /**
     * GroupRepositoryPlugin constructor.
     * @param GroupExtensionFactory $groupExtensionFactory
     */
    public function __construct(
        GroupExtensionFactory $groupExtensionFactory,
        ExtraAttributesRepository $extraAttributesRepository
    ) {
        $this->groupExtensionFactory = $groupExtensionFactory;
        $this->extraAttributesRepository = $extraAttributesRepository;
    }

    /**
     * Override save() method to save our extension attributes to extraAttributesRepository
     * @param $subject
     * @param callable $proceed
     * @param $group
     * @return mixed
     * @throws CouldNotSaveException
     */
    public function aroundSave($subject, callable $proceed, $group)
    {
        $nsExtension = $group->getExtensionAttributes();

        $group = $proceed($group);
        if ($nsExtension && $group) {
            $extraAttributes = $this->extraAttributesRepository->getById($group->getId());
            $extraAttributes->setCustomerGroupId($group->getId());
            $extraAttributes->setNetsuiteInternalId($nsExtension->getNetsuiteInternalId());
            $this->extraAttributesRepository->save($extraAttributes);

            $group->setExtensionAttributes($nsExtension);
        }

        return $group;
    }

    /**
     * Override getById() method to load extension attributes from extraAttributesRepository
     * @param $subject
     * @param $group
     * @return mixed
     */
    public function afterGetById($subject, $group)
    {
        $nsExtension = $group->getExtensionAttributes();
        if ($nsExtension === null) {
            $nsExtension = $this->groupExtensionFactory->create();
        }

        try {
            $extraAttributes = $this->extraAttributesRepository->getById($group->getId());
            if ($extraAttributes !== null) {
                $nsExtension->setNetsuiteInternalId($extraAttributes->getNetsuiteInternalId());
            }
        } catch (Exception $e) {// phpcs:ignore
        }

        $group->setExtensionAttributes($nsExtension);

        return $group;
    }
}
