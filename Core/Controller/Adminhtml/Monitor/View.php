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
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException;

class View extends Action implements HttpGetActionInterface
{
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private \MageOS\NetSuiteConnector\Core\Api\MonitorRegistryInterface $monitorRegistry,
        private \MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry $moduleRegistry
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

        $id = (int)$this->getRequest()->getParam('id');

        try {
            $monitorItem = $this->monitorRegistry->getById($id);
            if ($monitorItem === null) {
                throw new DataIntegrityException(sprintf('The ID %s does not exists!', $id));
            }

            $this->moduleRegistry->register('current_monitor_item', $monitorItem);
            /** @var \Magento\Framework\View\Result\Page $page */
            $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
            $page->setActiveMenu('MageOS_NetSuiteConnector::netsuite');
            $page->getConfig()->getTitle()->prepend(__('Monitor Dashboard'));
            return $page;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                'Something went wrong, please try again: ' . $e->getMessage()
            );
            $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $redirect->setPath('*/*');
        }
    }
}
