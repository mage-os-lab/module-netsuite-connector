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
namespace MageOS\NetSuiteConnector\Product\Model\Service;

class CustomerGroupProvider
{
    public function __construct(
        private readonly \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        private readonly \MageOS\NetSuiteConnector\Product\Model\Config\ProductConfig $productConfig,
        private readonly \MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger
    ) {
    }

    public function getCustomerGroupTierPrices() : ?\Magento\Customer\Api\Data\GroupInterface
    {
        $tierPriceCustomerGroup = $this->productConfig->getTierPriceCustomerGroup();
        try {
            $result = $this->groupRepository->getById($tierPriceCustomerGroup);
        } catch (\Exception $e) {
            $this->logger->addError('No Customer Group with id ' . $tierPriceCustomerGroup);
            $result = null;
        }
        return $result;
    }
}
