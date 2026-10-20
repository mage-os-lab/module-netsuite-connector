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

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;

/**
 * This class loads the NetSuite fields of the order into its extension attributes, so they are always present
 * and saving the order through the repository keeps them
 */
class AfterOrderRepositoryGet
{
    /**
     * Load the NetSuite fields into the extension attributes of the order
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $order
     * @return OrderInterface
     * @SuppressWarnings("unused")
     */
    public function afterGet(OrderRepositoryInterface $subject, OrderInterface $order): OrderInterface
    {
        $this->loadOrder($order);
        return $order;
    }

    /**
     * Load the NetSuite fields into the extension attributes of every order in the result
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderSearchResultInterface $searchResult
     * @return OrderSearchResultInterface
     * @SuppressWarnings("unused")
     */
    public function afterGetList(
        OrderRepositoryInterface $subject,
        OrderSearchResultInterface $searchResult
    ): OrderSearchResultInterface {
        foreach ($searchResult->getItems() as $order) {
            $this->loadOrder($order);
        }
        return $searchResult;
    }

    /**
     * Copy the NetSuite fields of the order into its extension attributes
     *
     * @param OrderInterface $order
     * @return void
     */
    private function loadOrder(OrderInterface $order): void
    {
        $extensionAttributes = $order->getExtensionAttributes();
        if (!$extensionAttributes) {
            return;
        }
        $extensionAttributes->setNetsuiteInternalId($order->getData('netsuite_internal_id'));
        $extensionAttributes->setNetsuiteLastImportDate($order->getData('netsuite_last_import_date'));
    }
}
