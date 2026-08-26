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

namespace MageOS\NetSuiteConnector\Order\Model;

use MageOS\NetSuiteConnector\Core\Exception\SkipRecordException;
use MageOS\NetSuiteConnector\Order\Model\PaymentProcessors\ProcessorInterface;

/**
 * This class is a factory for payment processors. It could create different objects depends on payment processor code
 * passed to method "create"
 */
class PaymentProcessorFactory
{
    /**
     * @var \MageOS\NetSuiteConnector\Order\Model\PaymentProcessorPool
     */
    private $paymentProcessorPool;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @param \MageOS\NetSuiteConnector\Order\Model\PaymentProcessorPool $paymentProcessorPool
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Order\Model\PaymentProcessorPool $paymentProcessorPool,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->paymentProcessorPool = $paymentProcessorPool;
        $this->objectManager = $objectManager;
    }

    /**
     * Create payment processor object based on given name
     *
     * @param string $code
     * @param array $data
     * @return ProcessorInterface
     * @throws \LogicException
     */
    public function create($code): ProcessorInterface
    {
        $code = strtolower($code);
        $paymentProcessors = $this->paymentProcessorPool->getAllPaymentProcessors();
        if (!isset($paymentProcessors[$code])) {
            throw new SkipRecordException(
                'There is no such payment processor: ' . $code
            );
        }
        return $paymentProcessors[$code];
    }
}
