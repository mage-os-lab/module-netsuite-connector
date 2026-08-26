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

use MageOS\NetSuiteConnector\Order\Api\OrderRegistryInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * This class is responsible for loading orders by NS internal ID. Loaded orders are cached inside the class variable.
 */
class OrderRegistry implements OrderRegistryInterface
{
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var array
     */
    private $orderCache = [];

    /**
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Get magento order by NetSuite ID
     *
     * @param type $netsuiteId
     * @return OrderInterface|null
     */
    public function getOrderByNetSuiteId($netsuiteId)
    {
        if (isset($this->orderCache[$netsuiteId])) {
            return $this->orderCache[$netsuiteId];
        }

        $this->searchCriteriaBuilder->addFilter('netsuite_internal_id', $netsuiteId);
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $magentoOrders = $this->orderRepository->getList($searchCriteria)->getItems();

        if (count($magentoOrders)) {
            $this->orderCache[$netsuiteId] = array_pop($magentoOrders);
            return $this->orderCache[$netsuiteId];
        } else {
            return null;
        }
    }
}
