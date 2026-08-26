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

/**
 * Class StripHtmlTagsMap - FE model for config
 */
class StripHtmlTagsMap extends AbstractNSFieldArray
{
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
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Catalog\Api\ProductAttributeRepositoryInterface $productAttributeRepository
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
        $this->addColumn('attribute', [
            'label' => __('Product Attribute'),
            'size' => 150
        ]);
        $this->addColumn('html_tags', [
            'label' => __('Comma-separated List of Allowed HTML Tags'),
        ]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add new attribute');
        parent::_construct();
    }

    /**
     * @inheritdoc
     */
    public function renderCellTemplate($columnName)
    {
        if ($columnName !== 'attribute') {
            return parent::renderCellTemplate($columnName);
        }

        $element = $this->elementFactory->create('select');
        $element->setForm(
            $this->getForm()
        )->setName(
            $this->_getCellInputElementName($columnName)
        )->setHtmlId(
            $this->_getCellInputElementId('<%- _id %>', $columnName)
        )->setValues(
            $this->productAttributeCodeList->getOptionList()
        );
        return str_replace("\n", '', $element->getElementHtml());
    }
}
