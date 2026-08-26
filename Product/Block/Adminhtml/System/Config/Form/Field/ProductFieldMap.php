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

namespace MageOS\NetSuiteConnector\Product\Block\Adminhtml\System\Config\Form\Field;

use MageOS\NetSuiteConnector\Core\Block\Adminhtml\System\Config\Form\Field\AbstractNSFieldArray;
use MageOS\NetSuiteConnector\Product\Model\Product\Map\Value;

/**
 * This class is responsible for rendering the field "Product synchronization" > "Field Mapping" on
 * the admin configuration form
 */
class ProductFieldMap extends AbstractNSFieldArray
{
    /**
     * @var array
     */
    private const DEFAULT_VALUES = [
        'netsuite' => '',
        'netsuite_list_id' => '',
        'netsuite_field_value' => ''
    ];

    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    private $elementFactory;

    /**
     * @var \MageOS\NetSuiteConnector\Product\Model\ProductAttributeCodeList
     */
    private $productAttributeCodeList;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Data\Form\Element\Factory $elementFactory
     * @param \MageOS\NetSuiteConnector\Product\Model\ProductAttributeCodeList $productAttributeCodeList
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        \MageOS\NetSuiteConnector\Product\Model\ProductAttributeCodeList $productAttributeCodeList,
        array $data = []
    ) {
        $this->elementFactory = $elementFactory;
        $this->productAttributeCodeList = $productAttributeCodeList;
        parent::__construct($context, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->addColumn('netsuite', [
            'label' => __('NetSuite Field'),
            'size' => 150,
            'class' => 'netsuite-field-input'
        ]);
        $this->addColumn('netsuite_settings', [
            'label' => __('NetSuite Settings')
        ]);
        $this->addColumn('magento', [
            'label' => __('Magento'),
            'size' => 150
        ]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add new mapping');

        $this->setTemplate('MageOS_NetSuiteConnector::system/config/form/field/productfieldmap.phtml');
        parent::_construct();
    }

    /**
     * @inheritdoc
     */
    protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
        $rowData = array_merge(self::DEFAULT_VALUES, $row->getData());
        $row->setData($rowData);
    }

    /**
     * @inheritdoc
     */
    public function renderCellTemplate($columnName)
    {
        if ($columnName == 'netsuite_settings' && isset($this->_columns[$columnName])) {
            $html = '<div class="productmap_producttype_select_container">';

            $element = $this->elementFactory->create('select');
            $element->setForm(
                $this->getForm()
            )->setName(
                $this->_getCellInputElementName($columnName)
            )->setHtmlId(
                $this->_getCellInputElementId('<%- _id %>', $columnName)
            )->setValues(
                $this->getNetsuiteFieldTypes()
            );

            $html .= str_replace("\n", '', $element->getElementHtml());

            $html .= '</div>';
            $html .= '<div style="display:none" class="netsuite_field_list_id"><br/>'
                . 'NetSuite List id: ' . $this->renderTextField('netsuite_list_id')
                . '</div>'
                . '<div style="display:none" class="netsuite_field_value"><br/>'
                . 'Value: ' . $this->renderTextField('netsuite_field_value')
                . '</div>';

            return str_replace("\n", '', $html);
        }

        if ($columnName == 'magento') {
            $element = $this->elementFactory->create('select');
            $element->setForm(
                $this->getForm()
            )->setName(
                $this->_getCellInputElementName($columnName)
            )->setHtmlId(
                $this->_getCellInputElementId('<%- _id %>', $columnName)
            )->setValues(
                $this->productAttributeCodeList->getOptionList(true)
            );
            return str_replace("\n", '', $element->getElementHtml());
        }

        return parent::renderCellTemplate($columnName);
    }

    /**
     * Get a list of available NS field types
     *
     * @return array
     */
    private function getNetsuiteFieldTypes(): array
    {
        return [
            ['value' => Value::FIELD_TYPE_STANDARD, 'label' => __('Standard Field')],
            ['value' => Value::FIELD_TYPE_CUSTOM_SIMPLE, 'label' => __('Custom Field - simple')],
            ['value' => Value::FIELD_TYPE_CUSTOM_LIST, 'label' => __('Custom Field - list')],
            ['value' => Value::FIELD_TYPE_CUSTOM_CHECKBOX, 'label' => __('Custom Field - checkbox')],
            ['value' => Value::FIELD_TYPE_CONSTANT_MAGENTO, 'label' => __('Constant Magento Value')],
        ];
    }

    /**
     * Get html for input text field
     *
     * @param string $columnName
     * @return string
     */
    private function renderTextField($columnName): string
    {
        $inputName  = $this->_getCellInputElementName($columnName);
        return '<input type="text" name="' . $inputName . '" value="<%- ' . $columnName . ' %>" />';
    }
}
