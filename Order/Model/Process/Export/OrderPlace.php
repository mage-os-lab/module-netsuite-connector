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

namespace MageOS\NetSuiteConnector\Order\Model\Process\Export;

use NetSuite\Classes\AddRequest;
use MageOS\NetSuiteConnector\Core\Api\Data\MessageInterface;
use MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\ResponseValidator;
use MageOS\NetSuiteConnector\Core\Model\Process\Export\AbstractExportProcessor;

/**
 * This class sends an order data to NS. This is performed in the method process. It receives an order data
 * in a queue message, prepares data for NS, sends an add request. If success - update an existing magento order
 * with NS internal ID.
 */
class OrderPlace extends AbstractExportProcessor
{
    public const MESSAGE_ACTION = 'order_place';

    private \Magento\Framework\Event\ManagerInterface $eventManager;
    private \Magento\Sales\Api\OrderRepositoryInterface $orderRepository;
    private \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement;
    private \MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry $registry;
    private \MageOS\NetSuiteConnector\Order\Model\Mapper\Order $orderMapper;
    private \Magento\Sales\Api\Data\OrderExtensionFactory $orderExtensionFactory;
    private \MageOS\NetSuiteConnector\Core\Api\MonitorManagementInterface $monitorManagement;

    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement,
        \Magento\Framework\Model\Context $context,
        \MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry $registry,
        \MageOS\NetSuiteConnector\Order\Model\Mapper\Order $orderMapper,
        \Magento\Sales\Api\Data\OrderExtensionFactory $orderExtensionFactory,
        \MageOS\NetSuiteConnector\Core\Api\MonitorManagementInterface $monitorManagement
    ) {
        $this->eventManager = $context->getEventDispatcher();
        $this->orderRepository = $orderRepository;
        $this->serviceManagement = $serviceManagement;
        $this->registry = $registry;
        $this->orderMapper = $orderMapper;
        $this->orderExtensionFactory = $orderExtensionFactory;
        $this->monitorManagement = $monitorManagement;
    }

    /**
     * Run order export for queue message
     *
     * @param MessageInterface $message
     * @throws \Exception
     */
    public function process(MessageInterface $message)
    {
        if (!$message || !$message->getItemId()) {
            throw new DataIntegrityException("Message not initialized");
        }

        $magentoOrder = $this->orderRepository->get($message->getItemId());
        if (!$magentoOrder || !$magentoOrder->getId()) {
            throw new DataIntegrityException("Cannot load order with id #{$message->getItemId()} from Magento!");
        }

        $netsuiteOrder = $this->orderMapper->getNetsuiteFormat($magentoOrder);

        $this->eventManager->dispatch(
            'netsuite_new_order_send_before',
            [
                'magento_order' => $magentoOrder,
                'netsuite_order' => $netsuiteOrder
            ]
        );

        $modifiedPayload = $this->monitorManagement->getModifiedObject($message);
        if ($modifiedPayload === null) {
            $this->monitorManagement->setModifiedObject($message, $netsuiteOrder);
        } else {
            $netsuiteOrder = $modifiedPayload;
        }

        $request = new AddRequest();
        $request->record = $netsuiteOrder;

        $netsuiteService = $this->serviceManagement->get();
        $response = $netsuiteService->add($request);
        ResponseValidator::validate($response);

        $netsuiteId = $response->writeResponse->baseRef->internalId;

        $extension = $magentoOrder->getExtensionAttributes();
        if (!$extension) {
            $magentoOrder->setExtensionAttributes($this->orderExtensionFactory->create());
        }
        $magentoOrder->getExtensionAttributes()->setNetsuiteInternalId($netsuiteId);
        $this->orderRepository->save($magentoOrder);

        $this->response = $response;
    }
}
