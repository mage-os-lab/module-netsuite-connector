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

namespace MageOS\NetSuiteConnector\Refund\Model;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use NetSuite\Classes\Record;
use MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\CustomFieldAccess;

/**
 * This class is responsible for search/update/create creditmemos in Magento. Loaded creditmemos are cached inside
 * the class variable.
 */
class MagentoCreditMemoRepository
{
    public const CUST_FIELD_NS_ORDER_ID = 'custbody_rw_cf_so_origin';
    private \Magento\Sales\Api\CreditmemoRepositoryInterface $creditMemoRepository;
    private \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder;
    private \Magento\Sales\Api\OrderRepositoryInterface $orderRepository;
    private \MageOS\NetSuiteConnector\Refund\Model\Mapper\CreditMemo $creditMemoMapper;
    private array $creditMemoCache = [];
    private array $orderCache = [];

    /**
     * @param \Magento\Sales\Api\CreditmemoRepositoryInterface $creditMemoRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \MageOS\NetSuiteConnector\Refund\Model\Mapper\CreditMemo $creditMemoMapper
     */
    public function __construct(
        \Magento\Sales\Api\CreditmemoRepositoryInterface $creditMemoRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \MageOS\NetSuiteConnector\Refund\Model\Mapper\CreditMemo $creditMemoMapper
    ) {
        $this->creditMemoRepository = $creditMemoRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository = $orderRepository;
        $this->creditMemoMapper = $creditMemoMapper;
    }

    /**
     * Load magento creditMemo based on NS internal ID
     *
     * @param int $internalNetSuiteId
     * @return CreditmemoInterface|null
     */
    public function getCreditMemoByNetSuiteId(int $internalNetSuiteId): ?CreditMemoInterface
    {
        if (isset($this->creditMemoCache[$internalNetSuiteId])) {
            return $this->creditMemoCache[$internalNetSuiteId];
        }

        $this->searchCriteriaBuilder->addFilter('netsuite_internal_id', $internalNetSuiteId);
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $magentoCreditMemos = $this->creditMemoRepository->getList($searchCriteria)->getItems();

        $this->creditMemoCache[$internalNetSuiteId] = count($magentoCreditMemos) ?
            array_pop($magentoCreditMemos) : null;
        return $this->creditMemoCache[$internalNetSuiteId];
    }

    /**
     * Method creates magento credit memo using information from the NetSuite CreditMemo or CashRefund
     * @param Record $netSuiteObject
     * @return CreditmemoInterface
     * @throws \Exception
     */
    public function createCreditMemo(Record $netSuiteObject): CreditMemoInterface
    {
        $netsuiteId = CustomFieldAccess::get($netSuiteObject, self::CUST_FIELD_NS_ORDER_ID);
        if (null === $netsuiteId) {
            throw new DataIntegrityException('[CreditMemoImport] Missed netsuite_internal_id for order');
        }
        $order = $this->getOrderByNetSuiteId((int)$netsuiteId);
        if (null === $order) {
            throw new DataIntegrityException(
                "[CreditMemoImport] Missed order with netsuite_internal_id {$netsuiteId}"
            );
        }
        $creditMemo = $this->creditMemoMapper->getMagentoFormat($netSuiteObject, $order);
        $creditMemo = $this->creditMemoRepository->save($creditMemo);
        //here will be logic for creation added
        return $creditMemo;
    }

    /**
     * Load magento order based on NS internal ID
     * @param int $internalNetSuiteId
     * @return OrderInterface|null
     */
    private function getOrderByNetSuiteId(int $internalNetSuiteId): ?OrderInterface
    {
        if (isset($this->orderCache[$internalNetSuiteId])) {
            return $this->orderCache[$internalNetSuiteId];
        }

        $this->searchCriteriaBuilder->addFilter('netsuite_internal_id', $internalNetSuiteId);
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $magentoOrders = $this->orderRepository->getList($searchCriteria)->getItems();

        $this->orderCache[$internalNetSuiteId] = count($magentoOrders) ? array_pop($magentoOrders) : null;
        return $this->orderCache[$internalNetSuiteId];
    }
}
