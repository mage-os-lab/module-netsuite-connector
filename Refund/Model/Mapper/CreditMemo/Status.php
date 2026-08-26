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
 *
 */

namespace MageOS\NetSuiteConnector\Refund\Model\Mapper\CreditMemo;

use Magento\Sales\Model\Order\Creditmemo;
use NetSuite\Classes\CashRefund;
use NetSuite\Classes\Record;

/**
 * This class maps NetSuite creditmemo/cashrefund status into magento creditmemo status
 */
class Status
{
    private const NS_CREDITMEMO_STATUS_OPEN = 'Open';
    private const NS_CREDITMEMO_STATUS_FULLY_APPLIED = 'Fully Applied';
    private const NS_CREDITMEMO_STATUS_CANCEL = 'Cancel';

    private const STATUS_MAP = [
        self::NS_CREDITMEMO_STATUS_OPEN => Creditmemo::STATE_OPEN,
        self::NS_CREDITMEMO_STATUS_FULLY_APPLIED => Creditmemo::STATE_REFUNDED,
        self::NS_CREDITMEMO_STATUS_CANCEL => Creditmemo::STATE_CANCELED,

    ];

    /**
     * Map NS creditmemo state into magento creditmemo state
     *
     * @param Record $netSuiteObject
     * @return int
     */
    public function mapStatus(Record $netSuiteObject): int
    {
        if ($netSuiteObject instanceof CashRefund) {
            return self::STATUS_MAP[self::NS_CREDITMEMO_STATUS_OPEN];
        }
        return in_array($netSuiteObject->status, array_keys(self::STATUS_MAP)) ?
            self::STATUS_MAP[$netSuiteObject->status] : self::STATUS_MAP[self::NS_CREDITMEMO_STATUS_OPEN];
    }
}
