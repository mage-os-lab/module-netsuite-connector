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
 */

namespace MageOS\NetSuiteConnector\Invoice\Model\Process\Export;

use MageOS\NetSuiteConnector\Core\Api\Data\MessageInterface;
use MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\ResponseValidator;
use MageOS\NetSuiteConnector\Core\Model\Process\Export\AbstractExportProcessor;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Model\Order;
use NetSuite\Classes\AddRequest;
use NetSuite\Classes\CashSale;
use NetSuite\Classes\InitializeRecord;
use NetSuite\Classes\InitializeRef;
use NetSuite\Classes\InitializeRequest;
use NetSuite\Classes\RecordType;

/**
 * This class sends an invoice data to NS. This is performed in the method process. It receives an invoice data
 * in a queue message, prepares data for NS, sends an initialize request. If success - it sends another request to NS
 * to add an invoice. If success - update an existing magento invoice with NS internal ID.
 */
class InvoiceSave extends AbstractExportProcessor
{
    public const MESSAGE_ACTION = 'invoice_save';

    private MessageInterface $currentMessage;

    private \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository;
    private \Magento\Sales\Api\OrderRepositoryInterface $orderRepository;
    private \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement;
    private \Magento\Framework\Event\ManagerInterface $eventManager;
    private \MageOS\NetSuiteConnector\Invoice\Model\Mapper\ExportInvoice $invoiceMapper;
    private \Magento\Sales\Api\Data\InvoiceExtensionFactory $invoiceExtensionFactory;
    private \MageOS\NetSuiteConnector\Core\Api\MonitorManagementInterface $monitorManagement;

    public function __construct(
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \MageOS\NetSuiteConnector\Invoice\Model\Mapper\ExportInvoice $invoiceMapper,
        \Magento\Sales\Api\Data\InvoiceExtensionFactory $invoiceExtensionFactory,
        \MageOS\NetSuiteConnector\Core\Api\MonitorManagementInterface $monitorManagement
    ) {

        $this->invoiceRepository = $invoiceRepository;
        $this->orderRepository = $orderRepository;
        $this->serviceManagement = $serviceManagement;
        $this->eventManager = $eventManager;
        $this->invoiceMapper = $invoiceMapper;
        $this->invoiceExtensionFactory = $invoiceExtensionFactory;
        $this->monitorManagement = $monitorManagement;
    }

    /**
     * Send invoice to NS
     *
     * Invoice data is received inside a queue message. This method prepares data for NS, sends an initialize request.
     * If success - it sends another request to NS to add an invoice. If success - update an existing magento invoice
     * with NS internal ID.
     *
     * @param MessageInterface $message
     * @throws DataIntegrityException
     */
    public function process(MessageInterface $message)
    {
        if (!$message || !$message->getItemId()) {
            throw new DataIntegrityException('Message not initialized');
        }

        $magentoInvoice = $this->invoiceRepository->get($message->getItemId());
        if (!$magentoInvoice || !$magentoInvoice->getId()) {
            throw new DataIntegrityException(
                "Cannot load invoice with id #{$message->getItemId()} from Magento!"
            );
        }
        $this->currentMessage = $message;

        $magentoOrder = $this->orderRepository->get($magentoInvoice->getOrderId());

        $cashSale = $this->sendInitializeRequest($magentoOrder);
        $netsuiteId = $this->sendAddRequest($cashSale, $magentoInvoice, $magentoOrder);

        $this->updateInvoice($magentoInvoice, $netsuiteId);
    }

    /**
     * Create an initialize NS-request for given order
     *
     * @param Order $magentoOrder
     * @return InitializeRequest
     */
    private function getInitializeRequest($magentoOrder): InitializeRequest
    {
        $initializeObject = new InitializeRecord();
        $initializeObject->reference = new InitializeRef();
        $initializeObject->reference->type = RecordType::salesOrder;
        $initializeObject->reference->internalId = $magentoOrder->getData('netsuite_internal_id') ?
            $magentoOrder->getData('netsuite_internal_id') :
            $magentoOrder->getOrigData('netsuite_internal_id');

        $initializeObject->type = RecordType::cashSale;

        $initializeRequest = new InitializeRequest();
        $initializeRequest->initializeRecord = $initializeObject;
        return $initializeRequest;
    }

    /**
     * Send an initialize request to NS and receive CashSale data object
     *
     * @param Order $magentoOrder
     * @return CashSale
     * @throws \RuntimeException
     */
    private function sendInitializeRequest($magentoOrder): CashSale
    {
        $netsuiteService = $this->serviceManagement->get();

        $initializeRequest = $this->getInitializeRequest($magentoOrder);
        $initializeResponse = $netsuiteService->initialize($initializeRequest);

        ResponseValidator::validate($initializeResponse);
        return $initializeResponse->readResponse->record;
    }

    /**
     * Create an add NS-request for given order
     *
     * @param CashSale $cashSale
     * @param InvoiceInterface $magentoInvoice
     * @param Order $magentoOrder
     * @return AddRequest
     */
    private function getAddRequest($cashSale, $magentoInvoice, $magentoOrder): AddRequest
    {
        if (isset($cashSale->shipGroupList)) {
            unset($cashSale->shipGroupList);
        }

        $cashSale = $this->invoiceMapper->cleanupNetsuiteCashSale($cashSale, $magentoInvoice, $magentoOrder);

        $this->eventManager->dispatch(
            'netsuite_new_cashsale_send_before',
            [
                'magento_invoice' => $magentoInvoice,
                'netsuite_cashsale' => $cashSale,
            ]
        );

        $modifiedPayload = $this->monitorManagement->getModifiedObject($this->currentMessage);
        if ($modifiedPayload === null) {
            $this->monitorManagement->setModifiedObject($this->currentMessage, $cashSale);
        } else {
            $cashSale = $modifiedPayload;
        }

        $addRequest = new AddRequest();
        $addRequest->record = $cashSale;
        return $addRequest;
    }

    /**
     * Send an add request to NS and receive NS internal id for exported invoice
     *
     * @param CashSale $cashSale
     * @param InvoiceInterface $magentoInvoice
     * @param Order $magentoOrder
     * @return string
     * @throws \RuntimeException
     */
    private function sendAddRequest($cashSale, $magentoInvoice, $magentoOrder)
    {
        $netsuiteService = $this->serviceManagement->get();

        $request = $this->getAddRequest($cashSale, $magentoInvoice, $magentoOrder);
        $response = $netsuiteService->add($request);

        ResponseValidator::validate($response);
        $this->response = $response;
        return $response->writeResponse->baseRef->internalId;
    }

    /**
     * Save NS internal ID to given invoice
     *
     * @param InvoiceInterface $magentoInvoice
     * @param string $netsuiteId
     */
    private function updateInvoice($magentoInvoice, $netsuiteId)
    {
        $extension = $magentoInvoice->getExtensionAttributes();
        if (null === $extension) {
            $magentoInvoice->setExtensionAttributes($this->invoiceExtensionFactory->create());
        }

        $magentoInvoice->getExtensionAttributes()->setNetsuiteInternalId($netsuiteId);
        $this->invoiceRepository->save($magentoInvoice);
    }
}
