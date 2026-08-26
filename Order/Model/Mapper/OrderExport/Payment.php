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

namespace MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use NetSuite\Classes\RecordRef;
use NetSuite\Classes\RecordType;
use NetSuite\Classes\SalesOrder;

/**
 * This class is responsible for adding payment information for NS order. This is used for a magento order export.
 */
class Payment
{
    /**
     * @var \Magento\Store\Api\StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @var \MageOS\NetSuiteConnector\Order\Model\Config\SalesConfig
     */
    private $salesConfig;

    /**
     * @var \MageOS\NetSuiteConnector\Order\Model\PaymentProcessorFactory
     */
    private $paymentProcessorFactory;

    /**
     * @param \Magento\Store\Api\StoreRepositoryInterface $storeRepository
     * @param \MageOS\NetSuiteConnector\Order\Model\Config\SalesConfig $salesConfig
     * @param \MageOS\NetSuiteConnector\Order\Model\PaymentProcessorFactory $paymentProcessorFactory
     */
    public function __construct(
        \Magento\Store\Api\StoreRepositoryInterface $storeRepository,
        \MageOS\NetSuiteConnector\Order\Model\Config\SalesConfig $salesConfig,
        \MageOS\NetSuiteConnector\Order\Model\PaymentProcessorFactory $paymentProcessorFactory
    ) {
        $this->storeRepository = $storeRepository;
        $this->salesConfig = $salesConfig;
        $this->paymentProcessorFactory = $paymentProcessorFactory;
    }

    /**
     * Add payment information for NS order record from magento order
     *
     * @param SalesOrder $netsuiteOrder
     * @param OrderInterface $magentoOrder
     */
    public function addPayment(SalesOrder $netsuiteOrder, OrderInterface $magentoOrder)
    {
        $this->addPaymentMethodData($netsuiteOrder, $magentoOrder);
        $this->addPaymentProcessorData($netsuiteOrder, $magentoOrder);
    }

    /**
     * Add information about payment method to NS order
     *
     * @param SalesOrder $netsuiteOrder
     * @param OrderInterface $magentoOrder
     */
    private function addPaymentMethodData(SalesOrder $netsuiteOrder, OrderInterface $magentoOrder)
    {
        $paymentMethodNetsuiteId = $this->getNetsuitePaymentMethodInternalId($magentoOrder->getPayment());
        if (null !== $paymentMethodNetsuiteId) {
            $netsuitePaymentMethod = new RecordRef();
            $netsuitePaymentMethod->type = RecordType::paymentMethod;
            $netsuitePaymentMethod->internalId = $paymentMethodNetsuiteId;
            $netsuiteOrder->paymentMethod = $netsuitePaymentMethod;
        }
    }

    /**
     * Add information about payment processor and add processed data to NS order
     *
     * @param SalesOrder $netsuiteOrder
     * @param OrderInterface $magentoOrder
     */
    private function addPaymentProcessorData(SalesOrder $netsuiteOrder, OrderInterface $magentoOrder)
    {
        $configItem = $this->getPaymentProcessorConfigItem($magentoOrder);
        if (null !== $configItem) {
            $netsuiteOrder->creditCardProcessor = new RecordRef();
            $netsuiteOrder->creditCardProcessor->internalId = $configItem['internal_netsuite_id'];

            $paymentProcessor = $this->paymentProcessorFactory->create($configItem['payment_processor']);
            $paymentProcessor->addProcessorSpecificInformationToNetSuiteOrder($netsuiteOrder, $magentoOrder);
        }
    }

    /**
     * Get config data from payment processor mapping for payment method and website of current order
     *
     * @param OrderInterface $magentoOrder
     * @return array|null
     */
    private function getPaymentProcessorConfigItem(OrderInterface $magentoOrder)
    {
        $paymentProcessorConfig = $this->salesConfig->getProcessorMapping();
        $websiteId = $this->getWebsiteIdFromStoreId($magentoOrder->getStoreId());

        if (!is_array($paymentProcessorConfig) || count($paymentProcessorConfig) == 0) {
            return null;
        }
        foreach ($paymentProcessorConfig as $configItem) {
            if ($configItem['payment_method'] == $magentoOrder->getPayment()->getMethod()
                && ($configItem['website'] == 0 || ($websiteId == $configItem['website']))
            ) {
                return $configItem;
            }
        }
        return null;
    }

    /**
     * Get NS internal ID for current order payment method based on configured mapping
     *
     * @param OrderPaymentInterface $magentoPaymentObject
     * @return int|null
     */
    private function getNetsuitePaymentMethodInternalId(OrderPaymentInterface $magentoPaymentObject)
    {
        $paymentMapping = $this->salesConfig->getNetsuiteMapping();
        foreach ($paymentMapping as $paymentMappingElement) {
            $paymentMethod = $paymentMappingElement['payment_method'] ?? null;
            if ($paymentMethod === $magentoPaymentObject->getMethod()) {
                if ($paymentMappingElement['payment_cc'] === '') {
                    return $paymentMappingElement['internal_netsuite_id'];
                }
                if ($magentoPaymentObject->getCcType() == $paymentMappingElement['payment_cc']) {
                    return $paymentMappingElement['internal_netsuite_id'];
                }
            }
        }

        return null;
    }

    /**
     * Get website id for given store
     *
     * @param int $storeId
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getWebsiteIdFromStoreId($storeId)
    {
        return $this->storeRepository->getById($storeId)->getWebsiteId();
    }
}
