<?php declare(strict_types=1);

/*
 *   RocketWeb
 *
 *  NOTICE OF LICENSE
 *
 *  This source file is subject to the Open Software License (OSL 3.0)
 *  that is bundled with this package in the file LICENSE.txt.
 *  It is also available through the world-wide-web at this URL:
 *  http://opensource.org/licenses/osl-3.0.php
 *
 *  @category  RocketWeb
 *  @package   MageOS_NetSuiteConnector
 *  @copyright Copyright (c) 2026 RocketWeb (http://rocketweb.com)
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  @author    Rocket Web Inc.
 *
 *
 */

namespace MageOS\NetSuiteConnector\Refund\Model\Mapper;

use Exception;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Creditmemo as CreditmemoModel;
use NetSuite\Classes\Record;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\ConvertDate;
use MageOS\NetSuiteConnector\Refund\Model\Mapper\CreditMemo\CreditMemoFactory;

/**
 * This class maps a magento creditmemo data using data from netsuite creditMemo/cashrefund record object
 */
class CreditMemo
{
    private \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory;
    private \MageOS\NetSuiteConnector\Refund\Model\Mapper\CreditMemo\Status $status;
    private \MageOS\NetSuiteConnector\Refund\Model\Mapper\CreditMemo\Items $items;
    private \MageOS\NetSuiteConnector\Refund\Model\Mapper\CreditMemo\CreditMemoFactory $objectCreator;

    /**
     * CreditMemo constructor.
     * @param \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory
     * @param  \MageOS\NetSuiteConnector\Refund\Model\Mapper\CreditMemo\CreditMemoFactory $objectCreator
     * @param \MageOS\NetSuiteConnector\Refund\Model\Mapper\CreditMemo\Status $status
     * @param \MageOS\NetSuiteConnector\Refund\Model\Mapper\CreditMemo\Items $items
     */
    public function __construct(
        \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory,
        \MageOS\NetSuiteConnector\Refund\Model\Mapper\CreditMemo\CreditMemoFactory $objectCreator,
        \MageOS\NetSuiteConnector\Refund\Model\Mapper\CreditMemo\Status $status,
        \MageOS\NetSuiteConnector\Refund\Model\Mapper\CreditMemo\Items $items
    ) {
        $this->creditmemoFactory = $creditmemoFactory;
        $this->status = $status;
        $this->items = $items;
        $this->objectCreator = $objectCreator;
    }

    /**
     * Create magento creditmemo based on given creditMemo data object (retrieved from NS)
     *
     * @param Record $netsuiteObject
     * @param OrderInterface $magentoOrder
     * @return CreditmemoModel
     * @throws Exception
     */
    public function getMagentoFormat(Record $netsuiteObject, OrderInterface $magentoOrder): CreditmemoInterface
    {
        $magentoCreditMemo = $this->objectCreator->createCreditMemo($netsuiteObject, $magentoOrder);

        $magentoCreditMemo->setAdjustment($netsuiteObject->total);
        $magentoCreditMemo->setSubtotal($netsuiteObject->subTotal);
        $magentoCreditMemo->setTaxAmount($netsuiteObject->taxTotal);
        $magentoCreditMemo->setBaseGrandTotal($netsuiteObject->total);
        $magentoCreditMemo->setGrandTotal($netsuiteObject->total);
        $magentoCreditMemo->setAdjustmentPositive($netsuiteObject->subTotal);
        $magentoCreditMemo->setState($this->status->mapStatus($netsuiteObject));
        $magentoCreditMemo->setItems($this->items->getItems($netsuiteObject, $magentoOrder));
        $magentoCreditMemo->setData('netsuite_internal_id', $netsuiteObject->internalId);
        $magentoCreditMemo->setData(
            'netsuite_last_import_date',
            ConvertDate::fromNetSuiteToSql($netsuiteObject->lastModifiedDate)
        );

        return $magentoCreditMemo;
    }
}
