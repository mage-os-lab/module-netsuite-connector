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


namespace MageOS\NetSuiteConnector\Shipment\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * This class provides an array of all carriers available for tracking
 */
class TrackingType implements OptionSourceInterface
{
    /**
     * @var \Magento\Shipping\Model\Config
     */
    private $shippingConfig;

    /**
     * @param \Magento\Shipping\Model\Config $shippingConfig
     */
    public function __construct(
        \Magento\Shipping\Model\Config $shippingConfig
    ) {
        $this->shippingConfig = $shippingConfig;
    }

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
     * Get all options as array
     *
     * @return array
     */
    public function toArray(): array
    {
        $options = [];
        $carriers = $this->shippingConfig->getAllCarriers();

        $options['custom'] = __('Custom Value');

        foreach ($carriers as $carrierCode => $carrierModel) {
            if ($carrierModel->isTrackingAvailable()) {
                $options[$carrierCode] = $carrierModel->getConfigData('title');
            }
        }

        return $options;
    }
}
