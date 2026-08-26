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

use Magento\Customer\Api\Data\GroupInterfaceFactory;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Controller\Adminhtml\Group\Save;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InvalidTransitionException;
use MageOS\NetSuiteConnector\Customer\Model\CustomerGroups\ExtraAttributesFactory;
use MageOS\NetSuiteConnector\Customer\Model\CustomerGroups\ExtraAttributesRepository;

/**
 * Class GroupAdminhtmlSavePlugin
 */
class GroupAdminhtmlSavePlugin
{
    /**
     * @var GroupRepositoryInterface
     */
    private $groupRepository;
    /**
     * @var GroupInterfaceFactory
     */
    private $groupDataFactory;
    /**
     * @var ExtraAttributesRepository
     */
    private $extraAttributesRepository;
    /**
     * @var ExtraAttributesFactory
     */
    private $extraAttributesFactory;

    /**
     * GroupAdminhtmlSavePlugin constructor.
     * @param GroupRepositoryInterface $groupRepository
     * @param GroupInterfaceFactory $groupDataFactory
     * @param ExtraAttributesRepository $extraAttributesRepository
     * @param ExtraAttributesFactory $extraAttributesFactory
     */
    public function __construct(
        GroupRepositoryInterface $groupRepository,
        GroupInterfaceFactory $groupDataFactory,
        ExtraAttributesRepository $extraAttributesRepository,
        ExtraAttributesFactory $extraAttributesFactory
    ) {
        $this->groupRepository = $groupRepository;
        $this->groupDataFactory = $groupDataFactory;
        $this->extraAttributesRepository = $extraAttributesRepository;
        $this->extraAttributesFactory = $extraAttributesFactory;
    }

    /**
     * Save extension attributes of customer group
     * @param $subject Save
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InvalidTransitionException
     */
    public function beforeExecute($subject)
    {
        $id = $subject->getRequest()->getParam('id');

        if ($id === null) {
            // now we create the group here and pass the ID to CustomerGroup\Save
            $customerGroup = $this->groupDataFactory->create();
            $customerGroup->setCode('-');

            $newGroup = $this->groupRepository->save($customerGroup);

            $subject->getRequest()->setParams([
                'id' => $newGroup->getId(),
            ]);

            $id = $newGroup->getId();
        }

        $extraAttrs = $this->extraAttributesFactory->create();

        $extraAttrs->setCustomerGroupId($id);
        $extraAttrs->setNetsuiteInternalId(
            $subject->getRequest()->getParam('netsuite_internal_id')
        );

        $this->extraAttributesRepository->save($extraAttrs);
    }
}
