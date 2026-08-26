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

namespace MageOS\NetSuiteConnector\Order\Model\PaymentProcessors;

use NetSuite\Classes\SalesOrder;

/**
 * Implementations of this interface are responsible for adding payment-specific data to NS order
 */
interface ProcessorInterface
{
    /**
     * Add payment data to NetSuite order
     *
     * @param SalesOrder $netsuiteOrder
     * @param \Magento\Sales\Model\Order $magentoOrder
     * @return SalesOrder
     */
    public function addProcessorSpecificInformationToNetSuiteOrder(
        SalesOrder $netsuiteOrder,
        \Magento\Sales\Model\Order $magentoOrder
    ): SalesOrder;
}
