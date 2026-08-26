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

namespace MageOS\NetSuiteConnector\Customer\Model\Process\Export;

use NetSuite\Classes\AddRequest;
use NetSuite\Classes\UpdateRequest;
use MageOS\NetSuiteConnector\Core\Api\Data\MessageInterface;
use MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\ResponseValidator;
use MageOS\NetSuiteConnector\Core\Model\Process\Export\AbstractExportProcessor;

/**
 * Class CustomerSave
 */
class CustomerSave extends AbstractExportProcessor
{
    public const MESSAGE_ACTION = 'customer_save';

    private \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface;
    private \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement;
    private \MageOS\NetSuiteConnector\Customer\Model\Mapper\Customer $customerMapperHelper;
    private \MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry $registry;
    private \MageOS\NetSuiteConnector\Core\Api\MonitorManagementInterface $monitorManagement;
    private \Magento\Framework\Event\ManagerInterface $eventManager;

    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement,
        \MageOS\NetSuiteConnector\Customer\Model\Mapper\Customer $customerMapperHelper,
        \Magento\Framework\Model\Context $context,
        \MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry $registry,
        \MageOS\NetSuiteConnector\Core\Api\MonitorManagementInterface $monitorManagement
    ) {
        $this->eventManager = $context->getEventDispatcher();
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->serviceManagement = $serviceManagement;
        $this->customerMapperHelper = $customerMapperHelper;
        $this->registry = $registry;
        $this->monitorManagement = $monitorManagement;
    }

    public function process(MessageInterface $message)
    {
        if (!$message || !$message->getItemId()) {
            throw new DataIntegrityException("Message not initialized");
        }
        $magentoCustomer = $this->customerRepositoryInterface->getById($message->getItemId());
        if (!$magentoCustomer || !$magentoCustomer->getId()) {
            throw new DataIntegrityException("Cannot load customer with id #{$message->getItemId()} from Magento!");
        }

        $this->registry->register('netsuite_skip_customer_export', true, true);

        $netsuiteService = $this->serviceManagement->get();
        $customerExists = $this->customerMapperHelper->findNetsuiteCustomer(
            'externalIdString',
            $this->customerMapperHelper->getExternalId($magentoCustomer)
        );
        $netsuiteCustomer = $this->customerMapperHelper->getNetsuiteFormat($magentoCustomer);

        $this->eventManager->dispatch(
            'netsuite_customer_send_before',
            ['netsuite_customer' => $netsuiteCustomer, 'magento_customer' => $magentoCustomer]
        );

        $modifiedPayload = $this->monitorManagement->getModifiedObject($message);
        if ($modifiedPayload === null) {
            $this->monitorManagement->setModifiedObject($message, $netsuiteCustomer);
        } else {
            $netsuiteCustomer = $modifiedPayload;
        }

        if (!$customerExists) {
            $request = new AddRequest();
            $request->record = $netsuiteCustomer;
            $response = $netsuiteService->add($request);
        } else {
            $request = new UpdateRequest();
            $netsuiteCustomer->internalId = $customerExists;
            unset($netsuiteCustomer->entityId);
            $request->record = $netsuiteCustomer;
            $response = $netsuiteService->update($request);
        }

        try {
            ResponseValidator::validate($response);
        } catch (DataIntegrityException $e) {
            if ($response->writeResponse->status->statusDetail[0]->code == 'DUP_ENTITY') {
                $request = new UpdateRequest();
                unset($netsuiteCustomer->entityId);
                $request->record = $netsuiteCustomer;
                $response = $netsuiteService->update($request);
            } else {
                throw $e;
            }
        }

        $netsuiteId = $response->writeResponse->baseRef->internalId;
        $magentoCustomer->setCustomAttribute('netsuite_internal_id', $netsuiteId);
        $this->customerRepositoryInterface->save($magentoCustomer);

        $this->response = $response;
    }
}
