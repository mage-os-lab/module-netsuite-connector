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
 * This class is responsible for rendering the field "Mapping between Magento payment methods and NetSuite payment
 * processors" on the admin configuration form
 */
class PaymentProcessorMap extends AbstractNSFieldArray
{
    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    private $elementFactory;

    /**
     * @var \Magento\Store\Api\WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * @var \MageOS\NetSuiteConnector\Order\Model\Config\Source\PaymentMethod
     */
    private $paymentMethodSource;

    /**
     * @var \MageOS\NetSuiteConnector\Order\Model\PaymentProcessorPool
     */
    private $paymentProcessorPool;

    /**
     * PaymentProcessorMap constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Data\Form\Element\Factory $elementFactory
     * @param \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepository
     * @param \MageOS\NetSuiteConnector\Order\Model\Config\Source\PaymentMethod $paymentMethodSource
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepository,
        \MageOS\NetSuiteConnector\Order\Model\Config\Source\PaymentMethod $paymentMethodSource,
        \MageOS\NetSuiteConnector\Order\Model\PaymentProcessorPool $paymentProcessorPool,
        array $data = []
    ) {
        $this->elementFactory = $elementFactory;
        $this->websiteRepository = $websiteRepository;
        $this->paymentMethodSource = $paymentMethodSource;
        $this->paymentProcessorPool = $paymentProcessorPool;
        parent::__construct($context, $data);
    }

    /**
     * @inheritdoc
     */
    public function _construct()
    {
        $this->addColumn('website', [
            'label' => __('Website'),
            'size'  => 28,
        ]);
        $this->addColumn('payment_method', [
            'label' => __('Payment Method'),
            'size'  => 28,
        ]);
        $this->addColumn('internal_netsuite_id', [
            'label' => __('NetSuite internal ID'),
            'size'  => 10,
        ]);
        $this->addColumn('payment_processor', [
            'label' => __('Payment Processor'),
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
        if ($columnName == 'payment_method') {
            $element = $this->elementFactory->create('select');

            $element->setForm(
                $this->getForm()
            )->setName(
                $this->_getCellInputElementName($columnName)
            )->setHtmlId(
                $this->_getCellInputElementId('<%- _id %>', $columnName)
            )->setValues(
                $this->paymentMethodSource->toOptionArray()
            );
            return str_replace("\n", '', $element->getElementHtml());
        }

        if ($columnName == 'website') {
            $element = $this->elementFactory->create('select');

            $element->setForm(
                $this->getForm()
            )->setName(
                $this->_getCellInputElementName($columnName)
            )->setHtmlId(
                $this->_getCellInputElementId('<%- _id %>', $columnName)
            )->setValues(
                $this->getAllWebsites()
            );
            return str_replace("\n", '', $element->getElementHtml());
        }

        if ($columnName == 'payment_processor') {
            $element = $this->elementFactory->create('select');

            $element->setForm(
                $this->getForm()
            )->setName(
                $this->_getCellInputElementName($columnName)
            )->setHtmlId(
                $this->_getCellInputElementId('<%- _id %>', $columnName)
            )->setValues(
                $this->getAllPaymentProcessors()
            );
            return str_replace("\n", '', $element->getElementHtml());
        }

        return parent::renderCellTemplate($columnName);
    }

    /**
     * Get all magento websites for the field "website"
     *
     * @return array
     */
    private function getAllWebsites(): array
    {
        $options = [];

        $websites = $this->websiteRepository->getList();
        foreach ($websites as $website) {
            $options[] = [
                'value' => $website->getId(),
                'label' => $website->getName()
            ];
        }

        return $options;
    }

    /**
     * Get registered payment processors for the field "payment_processor"
     *
     * @return array
     */
    private function getAllPaymentProcessors(): array
    {
        $options = [];
        foreach ($this->paymentProcessorPool->getAllPaymentProcessors() as $code => $class) {
            $options[] = ['value' => strtolower($code), 'label' => strtolower($code)];
        }
        return $options;
    }
}
