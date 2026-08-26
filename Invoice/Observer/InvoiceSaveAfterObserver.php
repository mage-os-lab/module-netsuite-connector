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

namespace MageOS\NetSuiteConnector\Invoice\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use MageOS\NetSuiteConnector\Core\Enum\Message\Queue;
use MageOS\NetSuiteConnector\Invoice\Model\ConfigProvider\Permissions;
use MageOS\NetSuiteConnector\Invoice\Model\Process\Export\InvoiceSave;

/**
 * This observer adds the invoice data to the queue after save. It will be exported to NS.
 */
class InvoiceSaveAfterObserver implements ObserverInterface
{
    private \MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig $connectorConfig;
    private \MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry $registry;
    private \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement;
    private \MageOS\NetSuiteConnector\Invoice\Model\ConfigProvider\Permissions $permissions;

    public function __construct(
        \MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig $connectorConfig,
        \MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry $registry,
        \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement,
        \MageOS\NetSuiteConnector\Invoice\Model\ConfigProvider\Permissions $permissions
    ) {

        $this->connectorConfig = $connectorConfig;
        $this->registry = $registry;
        $this->messageManagement = $messageManagement;
        $this->permissions = $permissions;
    }

    public function execute(Observer $observer): void
    {
        if (!$this->connectorConfig->isEnabled()) {
            return;
        }

        if (!$this->permissions->isFeatureEnabled(Permissions::SEND_INVOICES)) {
            return;
        }

        if ($this->registry->registry('netsuite_skip_invoice_export')) {
            return;
        }

        $invoice = $observer->getEvent()->getInvoice();
        if ($this->registry->registry('skip_invoice_export_queue_push_' . $invoice->getId())
            || $invoice->getData('netsuite_internal_id')
        ) {
            return;
        }

        $this->messageManagement->addMessageToQueue([
            InvoiceSave::MESSAGE_ACTION,
            (int)$invoice->getId(),
            (string)Queue::EXPORT()
        ]);
    }
}
