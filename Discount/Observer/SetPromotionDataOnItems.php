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

namespace MageOS\NetSuiteConnector\Discount\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\SalesRule\Api\Data\RuleInterface;
use NetSuite\Classes\CustomFieldList;
use NetSuite\Classes\DoubleCustomFieldRef;
use NetSuite\Classes\SalesOrderItem;
use MageOS\NetSuiteConnector\Core\Exception\MessageProcessor;

/**
 * PHPMD is complaining because we are using the NS classes
 * @suppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SetPromotionDataOnItems implements ObserverInterface
{
    private const PROMOTION_AMOUNT = 'custcol_rw_cc_promotion_amount';
    private const PROMOTION_RULES = 'custcol_rw_cc_promotion_rules';

    private array $rulesCache = [];
    private \Magento\SalesRule\Api\RuleRepositoryInterface $ruleRepository;
    private \MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger;
    private \MageOS\NetSuiteConnector\Discount\Model\Config\DiscountConfig $discountConfig;

    public function __construct(
        \Magento\SalesRule\Api\RuleRepositoryInterface $ruleRepository,
        \MageOS\NetSuiteConnector\Discount\Model\Config\DiscountConfig $discountConfig,
        \MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger
    ) {
        $this->ruleRepository = $ruleRepository;
        $this->logger = $logger;
        $this->discountConfig = $discountConfig;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        if (!$this->discountConfig->getAddPromotionData()) {
            return;
        }

        /** @var OrderItemInterface $magentoOrderItem */
        $magentoOrderItem = $observer->getEvent()->getData('magento_order_item');

        /** @var SalesOrderItem $netSuiteOrderItem */
        $netSuiteOrderItem = $observer->getEvent()->getData('netsuite_order_item');

        try {
            $promotionData = $this->getPromotionData($magentoOrderItem);
            if (!$promotionData) {
                return;
            }

            [$discount, $rules] = $promotionData;
            if ($discount) {
                $this->setPromotionAmount($netSuiteOrderItem, (float)$discount);
                if ($rules) {
                    $rules = explode(',', $rules);
                    $this->setPromotionRules($netSuiteOrderItem, $rules);
                }
            }
        } catch (\Throwable $e) {
            // We don't want to fail OrderExport because of something happening here, so we catch & log
            $logMessage = MessageProcessor::getMessagesAsString($e);
            $this->logger->error($logMessage);
        }
    }

    /**
     * Extract discount amount & applied rules either from current OR parent item
     *
     * @param OrderItemInterface $magentoOrderItem
     * @return array|null
     */
    private function getPromotionData(OrderItemInterface $magentoOrderItem): ?array
    {
        if ($magentoOrderItem->getDiscountAmount() > 0.001) {
            return [
                $magentoOrderItem->getDiscountAmount(),
                $magentoOrderItem->getAppliedRuleIds()
            ];
        }
        if ($magentoOrderItem->getParentItem() && $magentoOrderItem->getParentItem()->getDiscountAmount() > 0.001) {
            return [
                $magentoOrderItem->getParentItem()->getDiscountAmount(),
                $magentoOrderItem->getParentItem()->getAppliedRuleIds()
            ];
        }

        return null;
    }

    /**
     * Setting PROMOTION_RULES custom column on the Item
     *
     * @param SalesOrderItem $netSuiteOrderItem
     * @param array $ruleIds
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function setPromotionRules(SalesOrderItem $netSuiteOrderItem, array $ruleIds)
    {
        $ruleNames = [];
        foreach ($ruleIds as $ruleId) {
            $rule = $this->getRule((int)$ruleId);
            if ($rule) {
                $ruleNames[] = $rule->getName();
            }
        }

        if (empty($ruleNames)) {
            return;
        }

        $customFieldList = $netSuiteOrderItem->customFieldList ?? new CustomFieldList();
        $customFieldList->customField = $customFieldList->customField ?? [];

        $customField = new \NetSuite\Classes\StringCustomFieldRef();
        $customField->value = implode(',', $ruleNames);
        $customField->scriptId = self::PROMOTION_RULES;
        $customFieldList->customField[] = $customField;

        $netSuiteOrderItem->customFieldList = $customFieldList;
    }

    /**
     * Fetching rule from DB with cache
     *
     * @param int $ruleId
     * @return RuleInterface|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getRule(int $ruleId): ?RuleInterface
    {
        if (isset($this->rulesCache[$ruleId])) {
            return $this->rulesCache[$ruleId];
        }
        try {
            $this->rulesCache[$ruleId] = $this->ruleRepository->getById($ruleId);
            return $this->rulesCache[$ruleId];
        // phpcs:disable
        } catch (NoSuchEntityException $e) {}
        // phpcs:enable

        return null;
    }

    /**
     * Set PROMOTION_AMOUNT custom column on the Item
     *
     * @param SalesOrderItem $netSuiteOrderItem
     * @param float $amount
     */
    private function setPromotionAmount(SalesOrderItem $netSuiteOrderItem, float $amount)
    {
        $customFieldList = $netSuiteOrderItem->customFieldList ?? new CustomFieldList();
        $customFieldList->customField = $customFieldList->customField ?? [];

        $customField = new DoubleCustomFieldRef();
        $customField->value = -abs($amount);
        $customField->scriptId = self::PROMOTION_AMOUNT;
        $customFieldList->customField[] = $customField;

        $netSuiteOrderItem->customFieldList = $customFieldList;
    }
}
