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

namespace MageOS\NetSuiteConnector\Order\Plugin;

use Closure;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Handler\State;
use MageOS\NetSuiteConnector\Order\Model\ConfigProvider\Permissions;

/**
 * Class StateHandler - plugin to avoid status auto correlation for orders synced with NetSuite
 * We do not need this logic for synced because NetSuite is leading system for orders
 * @author akozyr
 */
class StateHandler
{
    /**
     * @var Permissions
     */
    private $permissions;
    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig
     */
    private $connectorConfig;

    /**
     * StateHandler constructor.
     * @param Permissions $permissions
     */
    public function __construct(
        Permissions $permissions,
        \MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig $connectorConfig
    ) {
        $this->permissions = $permissions;
        $this->connectorConfig = $connectorConfig;
    }

    /**
     * Disable an order state checking in case NetSuite connector is enabled
     *
     * @param State $subject
     * @param Closure $proceed
     * @param Order $order
     * @return State
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundCheck(
        State $subject,
        Closure $proceed,
        Order $order
    ): State {
        if (!$this->connectorConfig->isEnabled()
            || !$this->permissions->isFeatureEnabled(Permissions::SEND_ORDERS)
            || !$this->permissions->isFeatureEnabled(Permissions::GET_ORDER_CHANGES)
        ) {
            $subject = $proceed($order);
        }
        return $subject;
    }
}
