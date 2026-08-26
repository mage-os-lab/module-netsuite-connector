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
 * This class is responsible for rendering the field "Status Mapping" on the admin configuration form
 */
class OrderStatusMap extends AbstractNSFieldArray
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
        $this->addColumn('netsuite_status', [
            'label' => __('NetSuite Status'),
            'size'  => 28,
        ]);
        $this->addColumn('magento_status', [
            'label' => __('Magento Status'),
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
        if ($columnName == 'magento_status') {
            $element = $this->elementFactory->create('select');

            $element->setForm(
                $this->getForm()
            )->setName(
                $this->_getCellInputElementName($columnName)
            )->setHtmlId(
                $this->_getCellInputElementId('<%- _id %>', $columnName)
            )->setValues(
                $this->getMagentoStatuses()
            );
            return str_replace("\n", '', $element->getElementHtml());
        }

        return parent::renderCellTemplate($columnName);
    }

    /**
     * Get all magento statuses for the field "magento_status"
     *
     * @return array
     */
    private function getMagentoStatuses() : array
    {
        return [
            ['value' => \Magento\Sales\Model\Order::STATE_NEW, 'label' => __('New')],
            ['value' => \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT, 'label' => __('Pending payment')],
            ['value' => \Magento\Sales\Model\Order::STATE_PROCESSING, 'label' => __('Processing')],
            ['value' => \Magento\Sales\Model\Order::STATE_COMPLETE, 'label' => __('Complete')],
            ['value' => \Magento\Sales\Model\Order::STATE_CLOSED, 'label' => __('Closed')],
            ['value' => \Magento\Sales\Model\Order::STATE_CANCELED, 'label' => __('Canceled')],
            ['value' => \Magento\Sales\Model\Order::STATE_HOLDED, 'label' => __('Holded')],
            ['value' => \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW, 'label' => __('Payment Review')]
        ];
    }
}
