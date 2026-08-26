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

namespace MageOS\NetSuiteConnector\Invoice\Model\Process\Import;

use MageOS\NetSuiteConnector\Core\Model\Process\Import\AbstractImportProcessor;
use NetSuite\Classes\Record;
use NetSuite\Classes\RecordType;

/**
 * This class processes a cashSale record object imported from NS and create an invoice in magento DB
 */
class Cashsale extends AbstractImportProcessor
{
    public const MESSAGE_ACTION = 'cashsale';
    /**
     * @var \Magento\Sales\Api\InvoiceRepositoryInterface
     */
    private $invoiceRepository;

    /**
     * @var \MageOS\NetSuiteConnector\Order\Api\OrderRegistryInterface
     */
    private $orderRegistry;

    /**
     * @var \MageOS\NetSuiteConnector\Invoice\Model\Mapper\ImportInvoice
     */
    private $invoiceMapper;

    /**
     * @var \Magento\Sales\Api\InvoiceExtensionInterfaceFactory
     */
    private $invoiceExtensionFactory;

    /**
     * @var \MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry
     */
    private $registry;

    /**
     * @var \MageOS\NetSuiteConnector\Invoice\Model\InvoiceRegistry
     */
    private $invoiceRegistry;

    /**
     * @var int
     */
    private $recordLimit;

    /**
     * @param \MageOS\NetSuiteConnector\Invoice\Model\ConfigProvider\Permissions $permissionHelper
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository
     * @param \MageOS\NetSuiteConnector\Order\Api\OrderRegistryInterface $orderRegistry
     * @param \MageOS\NetSuiteConnector\Invoice\Model\Mapper\ImportInvoice $invoiceMapper
     * @param \Magento\Sales\Api\Data\InvoiceExtensionFactory $invoiceExtensionFactory
     * @param \MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry $registry
     * @param \MageOS\NetSuiteConnector\Invoice\Model\InvoiceRegistry $invoiceRegistry
     * @param int $recordLimit
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Invoice\Model\ConfigProvider\Permissions $permissionHelper,
        \Magento\Framework\Model\Context $context,
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement,
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
        \MageOS\NetSuiteConnector\Order\Api\OrderRegistryInterface $orderRegistry,
        \MageOS\NetSuiteConnector\Invoice\Model\Mapper\ImportInvoice $invoiceMapper,
        \Magento\Sales\Api\Data\InvoiceExtensionFactory $invoiceExtensionFactory,
        \MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry $registry,
        \MageOS\NetSuiteConnector\Invoice\Model\InvoiceRegistry $invoiceRegistry,
        $recordLimit = 10
    ) {
        $this->invoiceRepository = $invoiceRepository;
        $this->orderRegistry = $orderRegistry;
        $this->invoiceMapper = $invoiceMapper;
        $this->invoiceExtensionFactory = $invoiceExtensionFactory;
        $this->registry = $registry;
        $this->invoiceRegistry = $invoiceRegistry;
        $this->recordLimit = $recordLimit;
        parent::__construct($permissionHelper, $context, $serviceManagement);
    }

    /**
     * @inheritdoc
     */
    public function getPermissionName()
    {
        return \MageOS\NetSuiteConnector\Invoice\Model\ConfigProvider\Permissions::GET_CASH_SALES;
    }

    /**
     * @inheritdoc
     */
    public function getMessageType()
    {
        return self::MESSAGE_ACTION;
    }

    /**
     * @inheritdoc
     */
    public function getRecordType()
    {
        return RecordType::cashSale;
    }

    /**
     * @inheritdoc
     */
    public function isActive()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function isMagentoImportable(Record $invoice)
    {
        //We import an invoice only if it was not imported before. Invoice changes are not supported in Magento
        if ($this->invoiceRegistry->getInvoiceByNetSuiteId($invoice->internalId) !== null) {
            return false;
        }

        //If invoice is not part of an order placed inside Magento, skip it.
        if ($invoice->createdFrom === null) {
            return false;
        }
        $netsuiteOrderId = $invoice->createdFrom->internalId;

        if ($this->orderRegistry->getOrderByNetSuiteId($netsuiteOrderId) === null) {
            return false;
        }

        return true;
    }

    /**
     * Check whether given cashSale is already imported
     *
     * @param Record $record
     * @return boolean
     */
    public function isAlreadyImported(Record $record)
    {
        $invoice = $this->invoiceRegistry->getInvoiceByNetSuiteId($record->internalId);
        if (!$invoice) {
            return false;
        }
        $netsuiteUpdateDatetime = \MageOS\NetSuiteConnector\Core\Model\NetSuite\ConvertDate::fromNetSuiteToSql(
            $record->lastModifiedDate
        );
        if (strtotime($invoice->getData('netsuite_last_import_date')) > strtotime($netsuiteUpdateDatetime)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Update magento invoice with NS data from given $cashSale
     *
     * @param Record $cashSale
     */
    public function process(Record $cashSale)
    {
        //We don't import invoice updates, only the initial invoice
        if ($this->invoiceRegistry->getInvoiceByNetSuiteId($cashSale->internalId)) {
            return;
        }

        $this->eventManager->dispatch('netsuite_cash_sale_import_before', ['netsuite_item' => $cashSale]);

        $magentoInvoice = $this->invoiceMapper->getMagentoFormatFromCashSale($cashSale);

        $extension = $magentoInvoice->getExtensionAttributes();
        if ($extension === null) {
            $magentoInvoice->setExtensionAttributes($this->invoiceExtensionFactory->create());
        }
        $magentoInvoice->getExtensionAttributes()->setNetsuiteInternalId($cashSale->internalId);
        $this->invoiceRepository->save($magentoInvoice);

        $this->registry->register('skip_invoice_export_queue_push_' . $magentoInvoice->getEntityId(), 1);

        $this->eventManager->dispatch('netsuite_cash_sale_import_after', [
            'netsuite_item' => $cashSale, 'magento_invoice' => $magentoInvoice
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function getRecordLimit(): int
    {
        return $this->recordLimit;
    }
}
