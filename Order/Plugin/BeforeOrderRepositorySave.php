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

/**
 * This class transfers netsuite_internal_id from the extension data model to the order model itself
 */
class BeforeOrderRepositorySave
{
    /**
     * Copy netsuite_internal_id and netsuite_last_import_date from extension data object into target object
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $order
     * @return array
     */
    public function beforeSave(OrderRepositoryInterface $subject, OrderInterface $order): array
    {
        $extensionAttribute = $order->getExtensionAttributes();
        if ($extensionAttribute) {
            $order->setNetsuiteInternalId($extensionAttribute->getNetsuiteInternalId());
            $order->setNetsuiteLastImportDate($extensionAttribute->getNetsuiteLastImportDate());
        }

        return [$order];
    }
}
