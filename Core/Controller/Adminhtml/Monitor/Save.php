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
namespace MageOS\NetSuiteConnector\Core\Controller\Adminhtml\Monitor;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException;

class Save extends Action implements HttpPostActionInterface, HttpGetActionInterface
{
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private \MageOS\NetSuiteConnector\Core\Api\MonitorRegistryInterface $monitorRegistry
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        if (!$this->_authorization->isAllowed('MageOS_NetSuiteConnector::netsuite')) {
            /** @var \Magento\Framework\Controller\Result\Redirect $redirect */
            $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $redirect->setPath('admin/denied');
        }
        $postData = $this->getRequest()?->getPostValue('general');
        $id = $postData['monitor_id'] ?? 0;

        try {
            $monitorItem = $this->monitorRegistry->getById((int)$id);
            if ($monitorItem === null) {
                throw new DataIntegrityException(sprintf('The ID %s does not exists!', $id));
            }
            if (!$monitorItem->getHasPayload()) {
                throw new DataIntegrityException('Nothing to save, this entry does not have Data associated.');
            }

            $overwrite = (bool)($postData['overwrite_payload'] ?? false);
            $payload = $postData['payload'] ?? [];

            $monitorItem->setOverwritePayload($overwrite);

            // TODO: Validate JSON
            $monitorItem->setPayloadString($payload);
            if (empty($monitorItem->getPayload())) {
                throw new DataIntegrityException('Data is not a valid JSON format!');
            }
            $this->monitorRegistry->save($monitorItem);
            $this->messageManager->addSuccessMessage('Process Data updated!');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                'Something went wrong, please try again: ' . $e->getMessage()
            );
        }

        $redirectBack = $this->getRequest()->getParam('back', false);
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return ($redirectBack)
            ? $redirect->setPath('*/*/view', ['id' => $id])
            : $redirect->setPath('*/*/');
    }
}
