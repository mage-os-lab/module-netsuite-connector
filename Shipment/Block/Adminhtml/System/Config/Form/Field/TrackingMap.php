<?php declare(strict_types=1);
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

namespace MageOS\NetSuiteConnector\Shipment\Block\Adminhtml\System\Config\Form\Field;

use MageOS\NetSuiteConnector\Core\Block\Adminhtml\System\Config\Form\Field\AbstractNSFieldArray;

/**
 * This class is responsible for rendering the field "Mapping between Magento Tracking number types and Netsuite
 * shipping methods" on the admin configuration form
 */
class TrackingMap extends AbstractNSFieldArray
{
    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    private $elementFactory;

    /**
     * @var \MageOS\NetSuiteConnector\Shipment\Model\Config\Source\TrackingType
     */
    private $trackingType;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Data\Form\Element\Factory $elementFactory
     * @param \MageOS\NetSuiteConnector\Shipment\Model\Config\Source\TrackingType $trackingType
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        \MageOS\NetSuiteConnector\Shipment\Model\Config\Source\TrackingType $trackingType,
        array $data = []
    ) {
        $this->elementFactory = $elementFactory;
        $this->trackingType = $trackingType;
        parent::__construct($context, $data);
    }

    /**
     * @inheritdoc
     */
    public function _construct()
    {
        $this->addColumn('carrier_type', [
            'label' => __('Carrier'),
            'size'  => 28,
        ]);
        $this->addColumn('internal_netsuite_id', [
            'label' => __('NetSuite internal ID'),
            'size'  => 10,
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
        if ($columnName == 'carrier_type') {
            $element = $this->elementFactory->create('select');

            $element->setForm(
                $this->getForm()
            )->setName(
                $this->_getCellInputElementName($columnName)
            )->setHtmlId(
                $this->_getCellInputElementId('<%- _id %>', $columnName)
            )->setValues(
                $this->getCarriers()
            );
            return str_replace("\n", '', $element->getElementHtml());
        }

        return parent::renderCellTemplate($columnName);
    }

    /**
     * Get all allowed carriers with a tracking available
     *
     * @return array
     */
    private function getCarriers(): array
    {
        return $this->trackingType->toOptionArray();
    }
}
