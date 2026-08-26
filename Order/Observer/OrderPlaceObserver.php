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

namespace MageOS\NetSuiteConnector\Order\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MageOS\NetSuiteConnector\Core\Enum\Message\Queue;
use MageOS\NetSuiteConnector\Order\Model\ConfigProvider\Permissions;
use MageOS\NetSuiteConnector\Order\Model\Process\Export\OrderPlace;

/**
 * This observer adds the order data to the queue after save. It will be exported to NS.
 */
class OrderPlaceObserver implements ObserverInterface
{
    private \MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry $registry;
    private \MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig $connectorConfig;
    private \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement;
    private \MageOS\NetSuiteConnector\Order\Model\ConfigProvider\Permissions $permissions;

    /**
     * OrderPlaceObserver constructor.
     * @param \MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry $registry
     * @param \MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig $connectorConfig
     * @param \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement
     * @param \MageOS\NetSuiteConnector\Order\Model\ConfigProvider\Permissions $permissions
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry $registry,
        \MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig $connectorConfig,
        \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement,
        \MageOS\NetSuiteConnector\Order\Model\ConfigProvider\Permissions $permissions
    ) {
        $this->registry = $registry;
        $this->connectorConfig = $connectorConfig;
        $this->messageManagement = $messageManagement;
        $this->permissions = $permissions;
    }

    /**
     * Add placed order to the queue
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        if (!$this->connectorConfig->isEnabled()) {
            return $this;
        }

        if ($this->registry->registry('netsuite_skip_order_export')) {
            return $this;
        }

        if (!$this->permissions->isFeatureEnabled(Permissions::SEND_ORDERS)) {
            return $this;
        }

        $order = $observer->getEvent()->getOrder();

        $this->messageManagement->addMessageToQueue([
            OrderPlace::MESSAGE_ACTION,
            (int)$order->getId(),
            (string)Queue::EXPORT()
        ]);

        return $this;
    }
}
