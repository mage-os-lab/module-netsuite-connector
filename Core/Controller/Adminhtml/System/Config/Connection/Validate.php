<?php
namespace MageOS\NetSuiteConnector\Core\Controller\Adminhtml\System\Config\Connection;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use NetSuite\Classes\GetServerTimeRequest;
use MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig;

class Validate extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    /** @var \Magento\Framework\Controller\Result\JsonFactory */
    protected $_jsonFactory;

    /** @var ScopeConfigInterface  */
    protected $scopeConfig;
    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management
     */
    private $serviceManagement;

    /**
     * Validate constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement
     * @param JsonFactory $jsonFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Backend\App\Action\Context  $context,
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->_jsonFactory = $jsonFactory;
        $this->serviceManagement = $serviceManagement;
        parent::__construct($context);
    }

    public function execute()
    {
        $connectionData = [];
        $connectionData['host'] = $this->getRequest()->getParam('host');
        $connectionData['endpoint'] = $this->scopeConfig->getValue(ConnectorConfig::PATH_ENDPOINT);
        $connectionData['account_id'] = $this->getRequest()->getParam('account_id');
        $connectionData['consumer_key'] = $this->getRequest()->getParam('consumer_key');
        $connectionData['consumer_secret'] = $this->getRequest()->getParam('consumer_secret');
        $connectionData['token_id'] = $this->getRequest()->getParam('token_id');
        $connectionData['token_secret'] = $this->getRequest()->getParam('token_secret');

        $netsuiteService = $this->serviceManagement->get($connectionData);

        $netsuiteService->setSearchPreferences(false, 1);
        $getServerTimeRequest = new GetServerTimeRequest();

        $result = ['status' => 'success'];
        try {
            $response = $netsuiteService->getServerTime($getServerTimeRequest);
            if ($response === null) {
                $result['status'] = 'error';
                $result['message'] = 'Cannot connect';
            }
        } catch (\Exception $ex) {
            $result['status'] = 'error';
            $result['message'] = $ex->getMessage();
        }

        $jsonResult = $this->_jsonFactory->create();
        return $jsonResult->setData($result);
    }
}
