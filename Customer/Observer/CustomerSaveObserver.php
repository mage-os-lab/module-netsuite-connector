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
 *
 */

namespace MageOS\NetSuiteConnector\Customer\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MageOS\NetSuiteConnector\Core\Enum\Message\Queue;
use MageOS\NetSuiteConnector\Customer\Model\ConfigProvider\Permissions;
use MageOS\NetSuiteConnector\Customer\Model\Process\Export\CustomerSave;

/**
 * Class CustomerSaveObserver
 */
class CustomerSaveObserver implements ObserverInterface
{

    private \MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig $connectorConfig;
    private \MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry $registry;
    private \Magento\Sales\Api\OrderRepositoryInterface $orderRepositoryInterface;
    private \MageOS\NetSuiteConnector\Customer\Model\ConfigProvider\Permissions $permissions;
    private \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder;
    private \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement;

    public function __construct(
        \MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig $connectorConfig,
        \MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry $registry,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepositoryInterface,
        \MageOS\NetSuiteConnector\Customer\Model\ConfigProvider\Permissions $permissions,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement
    ) {
        $this->connectorConfig = $connectorConfig;
        $this->registry = $registry;
        $this->orderRepositoryInterface = $orderRepositoryInterface;
        $this->permissions = $permissions;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->messageManagement = $messageManagement;
    }

    public function execute(Observer $observer): void
    {
        if (!$this->connectorConfig->isEnabled()) {
            return;
        }

        if (!$this->permissions->isFeatureEnabled(Permissions::SEND_CUSTOMERS)) {
            return;
        }

        if ($this->registry->registry('netsuite_skip_customer_export')) {
            return;
        }

        $customer = $observer->getEvent()->getCustomer();

        // Prevent executing more than once per event.
        // The customer with 2 addresses that is saved in the admin will trigger the customer_save_after event 3 times
        if ($this->registry->registry('netsuite_customer_save_' . $customer->getId())) {
            return;
        }

        $this->registry->register('netsuite_customer_save_' . $customer->getId(), true);

        //do not send customer to Netsuite if he has no orders
        if (!$this->registry->registry('netsuite_force_send_customers') &&
            !$this->customerHasPlacedOrders((int)$customer->getId())) {
            return;
        }

        $this->messageManagement->addMessageToQueue([
            CustomerSave::MESSAGE_ACTION,
            (int)$customer->getId(),
            (string)Queue::EXPORT()
        ]);
    }

    public function customerHasPlacedOrders(int $customerId): bool
    {
        $this->searchCriteriaBuilder->addFilter('customer_id', $customerId);
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $magentoOrders = $this->orderRepositoryInterface->getList($searchCriteria)->getItems();

        return empty($magentoOrders) ? false : true;
    }
}
