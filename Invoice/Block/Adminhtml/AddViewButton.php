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
 */

namespace MageOS\NetSuiteConnector\Invoice\Block\Adminhtml;

/**
 * Add a "View in NetSuite" button on Invoice View page for easier access to NetSuite Sales Order
 */
class AddViewButton implements \MageOS\NetSuiteConnector\Core\Model\Button\ViewButtonProcessorInterface
{
    private \Magento\Framework\Registry $registry;

    public function __construct(
        \Magento\Framework\Registry $registry
    ) {
        $this->registry = $registry;
    }

    public function belongsToBlock(\Magento\Framework\View\Element\AbstractBlock $block): bool
    {
        return ($block instanceof \Magento\Sales\Block\Adminhtml\Order\Invoice\View);
    }

    public function addButton(
        \Magento\Backend\Block\Widget\Button\ButtonList $buttonList,
        string $netsuiteBaseUrl
    ): void {
        $invoice = $this->registry->registry('current_invoice');
        if (!$invoice) {
            return;
        }
        $netsuiteInternalId = $invoice->getData('netsuite_internal_id');
        if (!$netsuiteInternalId) {
            return;
        }

        $viewUrl = $netsuiteBaseUrl . 'app/accounting/transactions/cashsale.nl?id=' . $netsuiteInternalId;

        $buttonList->add(
            'view_in_netsuite',
            [
                'label' => __('View in NetSuite'),
                'onclick' => 'window.open(\'' . $viewUrl . '\',\'_blank\')',
                'class' => 'go'
            ]
        );
    }
}
