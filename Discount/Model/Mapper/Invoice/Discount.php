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

namespace MageOS\NetSuiteConnector\Discount\Model\Mapper\Invoice;

use Magento\Sales\Api\Data\InvoiceInterface;
use MageOS\NetSuiteConnector\Core\Exception\ConnectorRuntimeException;

/**
 * This class prepares a NS cashSaleItem to represent discount
 */
class Discount
{
    private \MageOS\NetSuiteConnector\Discount\Model\Config\DiscountConfig $discountConfig;
    private \MageOS\NetSuiteConnector\Discount\Model\Mapper\Invoice\DiscountProviderInterface $provider;

    public function __construct(
        \MageOS\NetSuiteConnector\Discount\Model\Config\DiscountConfig $discountConfig,
        array $providers = []
    ) {
        if (!$discountConfig->getOrderSkipDiscount()) {
            $provider = $providers[$discountConfig->getLogicSwitch()] ?? null;

            if (!($provider instanceof DiscountProviderInterface)) {
                throw new ConnectorRuntimeException('Discount Provider mismatch with Interface!');
            }

            $this->provider = $provider;
        }

        $this->discountConfig = $discountConfig;
    }

    public function isNSDiscountItem($netsuiteItem)
    {
        if ($this->discountConfig->getOrderSkipDiscount()) {
            return null;
        }
        return $this->provider->isNSDiscountItem($netsuiteItem);
    }

    public function updateNSDiscountItem($netsuiteItem, $discountValue, $discountDescription)
    {
        if ($this->discountConfig->getOrderSkipDiscount()) {
            return;
        }
        $this->provider->updateNSDiscountItem($netsuiteItem, $discountValue, $discountDescription);
    }

    public function addNSDiscountItem($cashSale, InvoiceInterface $magentoInvoice)
    {
        if ($this->discountConfig->getOrderSkipDiscount()) {
            return;
        }
        $this->provider->addNSDiscountItem($cashSale, $magentoInvoice);
    }
}
