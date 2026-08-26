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

namespace MageOS\NetSuiteConnector\Customer\Block\Adminhtml\Group\Edit;

use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Block\Adminhtml\Group\Edit\Form as ParentForm;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class Form
 */
class Form extends ParentForm
{
    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $form = $this->getForm();
        $groupId = $this->_coreRegistry->registry(RegistryConstants::CURRENT_GROUP_ID);

        /** @var GroupInterface $customerGroup */
        if ($groupId === null) {
            $customerGroup = $this->groupDataFactory->create();
        } else {
            $customerGroup = $this->_groupRepository->getById($groupId);
        }

        $fieldset = $form->addFieldset('netsuite', ['legend' => __('NetSuite')]);

        $extAttrs = $customerGroup->getExtensionAttributes();

        $fieldset->addField(
            'netsuite_internal_id',
            'text',
            [
                'name' => 'netsuite_internal_id',
                'label' => __('NetSuite internal ID'),
                'title' => __('NetSuite internal ID'),
                'required' => false,
                'value' => $extAttrs === null ? '' : $extAttrs->getNetsuiteInternalId()
            ]
        );
    }
}
