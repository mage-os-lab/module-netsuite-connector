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

namespace MageOS\NetSuiteConnector\Core\Controller\Adminhtml\Monitor;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use MageOS\NetSuiteConnector\Core\Model\MutexFactory;

class Process extends Action implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * Process constructor.
     * @param Context $context
     * @param \MageOS\NetSuiteConnector\Core\Model\Process $process
     * @param Registry $registry
     * @param MutexFactory $mutexFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private \MageOS\NetSuiteConnector\Core\Model\Process $process,
        private \Magento\Framework\Registry $registry,
        private \MageOS\NetSuiteConnector\Core\Model\MutexFactory $mutexFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|Redirect|ResultInterface
     */
    public function execute()
    {
        /**
         * TODO: Refactor to process only given line
         */
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($this->getRequest()->getParam('mode') === 'export') {
            $modes = ['export'];
        } else {
            $modes = ['import'];
        }

        $mutex = $this->mutexFactory->createQueueMutex($modes);
        if ($mutex->getLock()) {
            try {
                $this->registry->register('current_run_mode', $modes[0]);
                if ($modes[0] === 'export') {
                    $this->process->processExport();
                } else {
                    $this->process->processImport();
                }
            } catch (Exception $ex) {
                $this->messageManager->addError(__($ex->getMessage()));
                return $resultRedirect->setPath('netsuite/queue/' . $modes[0]);
            }

            $this->messageManager->addSuccess(__('Queue processed successfully'));
            return $resultRedirect->setPath('netsuite/queue/' . $modes[0]);

        } else {
            $this->messageManager->addError(__('Cron already running'));
            return $resultRedirect->setPath('netsuite/queue/' . $modes[0]);
        }
    }
}
