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

namespace MageOS\NetSuiteConnector\Order\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use NetSuite\Classes\SalesOrderOrderStatus;

/**
 * This class provides an array of default NS order statuses
 */
class DefaultOrderStatus implements OptionSourceInterface
{
    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = $this->toArray();
        $return = [];
        foreach ($options as $key => $value) {
            $return[] = ['label' => $value, 'value' => $key];
        }
        return $return;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            SalesOrderOrderStatus::_pendingApproval => __('_pendingApproval'),
            SalesOrderOrderStatus::_pendingFulfillment => __('_pendingFulfillment'),
        ];
    }
}
