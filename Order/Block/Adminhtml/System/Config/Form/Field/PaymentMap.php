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

/**
 * This class is responsible for rendering the field "Mapping between Magento and NetSuite payment methods" on
 * the admin configuration form
 */
class PaymentMap extends AbstractNSFieldArray
{
    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    private $elementFactory;

    /**
     * @var \Magento\Payment\Model\Config
     */
    private $paymentConfig;

    /**
     * @var \MageOS\NetSuiteConnector\Order\Model\Config\Source\PaymentMethod
     */
    private $paymentMethodSource;

    /**
     * PaymentMap constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Data\Form\Element\Factory $elementFactory
     * @param \Magento\Payment\Model\Config $paymentConfig
     * @param \MageOS\NetSuiteConnector\Order\Model\Config\Source\PaymentMethod $paymentMethodSource
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        \Magento\Payment\Model\Config $paymentConfig,
        \MageOS\NetSuiteConnector\Order\Model\Config\Source\PaymentMethod $paymentMethodSource,
        array $data = []
    ) {
        $this->elementFactory = $elementFactory;
        $this->paymentConfig = $paymentConfig;
        $this->paymentMethodSource = $paymentMethodSource;
        parent::__construct($context, $data);
    }

    /**
     * @inheritdoc
     */
    public function _construct()
    {
        $this->addColumn('payment_method', [
            'label' => __('Payment Method'),
            'size'  => 28,
        ]);
        $this->addColumn('payment_cc', [
            'label' => __('Credit Card'),
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
        if ($columnName == 'payment_method' || $columnName=='payment_cc') {
            $element = $this->elementFactory->create('select');

            $element->setForm(
                $this->getForm()
            )->setName(
                $this->_getCellInputElementName($columnName)
            )->setHtmlId(
                $this->_getCellInputElementId('<%- _id %>', $columnName)
            )->setValues(
                ($columnName == 'payment_method')
                    ? $this->paymentMethodSource->toOptionArray()
                    : $this->getPaymentCCTypes()
            );
            return str_replace("\n", '', $element->getElementHtml());
        }

        return parent::renderCellTemplate($columnName);
    }

    /**
     * Get all magento CC types for the field "payment_cc"
     *
     * @return array
     */
    private function getPaymentCCTypes(): array
    {
        $options = [];
        $ccTypes = $this->paymentConfig->getCcTypes();
        $options[] = ['value' => '', 'label' => __('All')];
        foreach ($ccTypes as $code => $title) {
            $options[] = ['value' => $code, 'label' => $title];
        }
        return $options;
    }
}
