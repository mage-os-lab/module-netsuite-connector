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

namespace MageOS\NetSuiteConnector\Customer\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Exception\LocalizedException;
use MageOS\NetSuiteConnector\Core\Block\Adminhtml\System\Config\Form\Field\AbstractNSFieldArray;

/**
 * Class PriceLevelMap
 */
class PriceLevelMap extends AbstractNSFieldArray
{
    /**
     * @var Factory
     */
    protected $elementFactory;

    /**
     * Customer groups cache
     *
     * @var array
     */
    private $customerGroups;

    /**
     * @var GroupRepositoryInterface
     */
    private $groupRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param Context $context
     * @param Factory $elementFactory
     * @param GroupRepositoryInterface $groupRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param array $data
     */
    public function __construct(
        Context $context,
        Factory $elementFactory,
        GroupRepositoryInterface $groupRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        array $data = []
    ) {
        $this->elementFactory = $elementFactory;
        $this->groupRepository = $groupRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->addColumn('customer_group', [
            'label' => __('Customer Group'),
            'size' => 28,
        ]);
        $this->addColumn('price_level', [
            'label' => __('NetSuite Price Level'),
            'size' => 28,
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add new mapping');

        parent::_construct();
    }

    /**
     * {@inheritdoc}
     */
    public function renderCellTemplate($columnName)
    {
        if ($columnName == 'customer_group') {
            $element = $this->elementFactory->create('select');

            $element->setForm(
                $this->getForm()
            )->setName(
                $this->_getCellInputElementName($columnName)
            )->setHtmlId(
                $this->_getCellInputElementId('<%- _id %>', $columnName)
            )->setValues(
                $this->getCustomerGroups()
            );
            return str_replace("\n", '', $element->getElementHtml());
        }

        return parent::renderCellTemplate($columnName);
    }

    /**
     * Get list of value-label for customer groups
     *
     * @return array
     * @throws LocalizedException
     */
    protected function getCustomerGroups()
    {
        if ($this->customerGroups === null) {
            $this->customerGroups = [];
            foreach ($this->groupRepository->getList($this->searchCriteriaBuilder->create())->getItems() as $item) {
                $this->customerGroups[] = ['value' => $item->getId(), 'label' => $item->getCode()];
            }
        }
        return $this->customerGroups;
    }
}
