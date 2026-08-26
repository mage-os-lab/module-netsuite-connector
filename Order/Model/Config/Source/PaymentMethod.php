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
use Magento\Store\Model\ScopeInterface;

/**
 * This class provides an array of all magento payment methods
 */
class PaymentMethod implements OptionSourceInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Payment\Model\Method\Factory
     */
    private $paymentMethodFactory;

    /**
     * @var array
     */
    private $options;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Factory $paymentMethodFactory
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Factory $paymentMethodFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->paymentMethodFactory = $paymentMethodFactory;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        if (null !== $this->options) {
            return $this->options;
        }
        $this->options = [];
        $payments = [];
        foreach ($this->scopeConfig->getValue('payment', ScopeInterface::SCOPE_STORE, null) as $code => $data) {
            if (isset($data['active']) && (bool)$data['active'] && isset($data['model'])) {
                try {
                    $methodModel = $this->paymentMethodFactory->create($data['model']);
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    continue;
                }
                $methodModel->setStore(null);
                if ($methodModel->getConfigData('active', null)) {
                    $payments[$code] = $methodModel;
                }
            }
        }

        foreach ($payments as $paymentCode => $paymentModel) {
            $paymentTitle = $this->scopeConfig->getValue("payment/$paymentCode/title");
            $this->options[] = ['value' => $paymentCode, 'label' => $paymentTitle];
        }

        $this->options[] = ['value' => 'paypal_express', 'label' => __('PayPal Express')];

        return $this->options;
    }
}
