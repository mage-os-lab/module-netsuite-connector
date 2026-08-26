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

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use NetSuite\Classes\Record;
use MageOS\NetSuiteConnector\Core\Exception\ConnectorRuntimeException;
use MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\CustomFieldAccess;

/**
 * This class creates creditMemo new object using order or invoice depending on params from NetSuite
 */
class CreditMemoFactory
{
    public const CUST_FIELD_REFUND_IN_MAGENTO = 'custbody_rw_cf_refund_in_magento';

    private \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory;

    /**
     * CreditMemoFactory constructor.
     * @param \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory
     */
    public function __construct(\Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory)
    {
        $this->creditmemoFactory = $creditmemoFactory;
    }

    /**
     * Method initialize new Magento CreditMemo based on the data from NetSuite
     * if NetSuite Object (CashRefund/CreditMemo) have Refund In Magento cust field set to true
     * we should create Magento CreditMemo from the invoice
     * if not - we should create from order
     * to get order and invoice we will use another custom field - custbody_rw_cf_so_origin
     *
     * @param Record $netsuiteObject
     * @param OrderInterface $order
     * @return CreditmemoInterface
     * @throws ConnectorRuntimeException
     * @throws DataIntegrityException
     */
    public function createCreditMemo(Record $netsuiteObject, OrderInterface $order): CreditMemoInterface
    {
        $refund = CustomFieldAccess::get($netsuiteObject, self::CUST_FIELD_REFUND_IN_MAGENTO);
        if (false === (bool)$refund) {
            return $this->creditmemoFactory->createByOrder($order);
        }
        $invoices = $order->getInvoiceCollection()->getItems();
        if (count($invoices) !== 1) {
            throw new ConnectorRuntimeException(
                "[CreditMemoImport] No invoice found " .
                "or more then one invoice found for order with id {$order->getEntityId()}"
            );
        }
        $invoice = array_shift($invoices);

        return $this->creditmemoFactory->createByInvoice($invoice);
    }
}
