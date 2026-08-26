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
 * This class is responsible for rendering the field "Mapping between Magento and NetSuite shipping methods" on
 * the admin configuration form
 */
class ShippingMap extends AbstractNSFieldArray
{
    private \Magento\Framework\Data\Form\Element\Factory $elementFactory;
    private \Magento\Shipping\Model\Config $shippingConfig;
    private \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig;

    /**
     * ShippingMap constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Data\Form\Element\Factory $elementFactory
     * @param \Magento\Shipping\Model\Config $shippingConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        \Magento\Shipping\Model\Config $shippingConfig,
        array $data = []
    ) {
        $this->elementFactory = $elementFactory;
        $this->shippingConfig = $shippingConfig;
        $this->scopeConfig = $context->getScopeConfig();
        parent::__construct($context, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->addColumn('shipping_method', [
            'label' => __('Shipping Method'),
            'size'  => 28,
        ]);
        $this->addColumn('shipping_description', [
            'label' => __('Shipping Description'),
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
        if ($columnName == 'shipping_method') {
            $element = $this->elementFactory->create('select');

            $element->setForm(
                $this->getForm()
            )->setName(
                $this->_getCellInputElementName($columnName)
            )->setHtmlId(
                $this->_getCellInputElementId('<%- _id %>', $columnName)
            )->setValues(
                $this->getAllShippingMethods()
            );
            return str_replace("\n", '', $element->getElementHtml());
        }

        return parent::renderCellTemplate($columnName);
    }

    /**
     * Get all allowed shipping methods
     *
     * @return array
     */
    private function getAllShippingMethods(): array
    {
        $carriers = $this->shippingConfig->getActiveCarriers();
        $options = [];

        foreach ($carriers as $carrierCode => $carrierModel) {
            if ($methods = $carrierModel->getAllowedMethods()) {
                $title = $this->scopeConfig->getValue("carriers/$carrierCode/title");
                if (!$title) {
                    $title = $carrierCode;
                }
                foreach ($methods as $mcode => $method) {
                        $code = $carrierCode . '_' . $mcode;
                        $options[] = ['value' => $code, 'label' => $title.' - '.$method];
                }
            }
        }

        return $options;
    }
}
