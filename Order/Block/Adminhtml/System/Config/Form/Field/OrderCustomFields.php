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
 */

declare(strict_types=1);

namespace MageOS\NetSuiteConnector\Order\Block\Adminhtml\System\Config\Form\Field;

use MageOS\NetSuiteConnector\Core\Block\Adminhtml\System\Config\Form\Field\AbstractNSFieldArray;
use MageOS\NetSuiteConnector\Order\Model\CustomFields;

/**
 * This class is responsible for rendering the field "Custom fields that are to be synched between Magento and NetSuite"
 * on the admin configuration form
 */
class OrderCustomFields extends AbstractNSFieldArray
{
    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    private $elementFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Data\Form\Element\Factory $elementFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        array $data = []
    ) {
        $this->elementFactory = $elementFactory;
        parent::__construct($context, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->addColumn('netsuite_field_name', [
            'label' => __('NetSuite field name'),
            'size'  => 28,
        ]);
        $this->addColumn('netsuite_field_type', [
            'label' => __('NetSuite field type'),
            'size'  => 28,
        ]);
        $this->addColumn('netsuite_list_internal_id', [
            'label' => __('NetSuite list internal id (list type only)'),
            'size'  => 28,
        ]);
        $this->addColumn('value_type', [
            'label' => __('Value Type'),
            'size'  => 28,
        ]);
        $this->addColumn('value', [
            'label' => __('Value'),
            'size'  => 28,
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add new mapping');

        parent::_construct();
    }

    /**
     * @inheritdoc
     */
    public function renderCellTemplate($columnName): string
    {
        if ($columnName == 'value_type') {
            $element = $this->elementFactory->create('select');

            $element->setForm(
                $this->getForm()
            )->setName(
                $this->_getCellInputElementName($columnName)
            )->setHtmlId(
                $this->_getCellInputElementId('<%- _id %>', $columnName)
            )->setValues(
                $this->getValueTypes()
            );
            return str_replace("\n", '', $element->getElementHtml());
        }

        if ($columnName == 'netsuite_field_type') {
            $element = $this->elementFactory->create('select');

            $element->setForm(
                $this->getForm()
            )->setName(
                $this->_getCellInputElementName($columnName)
            )->setHtmlId(
                $this->_getCellInputElementId('<%- _id %>', $columnName)
            )->setValues(
                $this->getNetSuiteFieldTypes()
            );
            return str_replace("\n", '', $element->getElementHtml());
        }

        return parent::renderCellTemplate($columnName);
    }

    /**
     * Return options for the field "value_type"
     *
     * @return array
     */
    private function getValueTypes(): array
    {
        return [
            ['value' => CustomFields::VALUE_TYPE_FIXED, 'label' => __('Fixed Value')],
            ['value' => CustomFields::VALUE_TYPE_ORDER_ATTRIBUTE, 'label' => __('Magento Order Attribute')]
        ];
    }

    /**
     * Return options for the field "netsuite_field_type"
     *
     * @return array
     */
    private function getNetSuiteFieldTypes(): array
    {
        return [
            ['value' => CustomFields::TYPE_SIMPLE, 'label' => __('Custom field - simple (string, number etc)')],
            ['value' => CustomFields::TYPE_LIST, 'label' => __('Custom field - List')],
            ['value' => CustomFields::TYPE_STANDARD, 'label' => __('Standard field')],
            ['value' => CustomFields::TYPE_STANDARD_RECORD_REF, 'label' => __('Standard Record Ref')]
        ];
    }
}
