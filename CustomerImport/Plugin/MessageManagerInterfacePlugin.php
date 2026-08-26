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

namespace MageOS\NetSuiteConnector\CustomerImport\Plugin;

use Magento\Framework\Message\ManagerInterface;

/**
 * Class MessageManagerInterfacePlugin - adds logic for not set password message show
 */
class MessageManagerInterfacePlugin
{
    private \Magento\Framework\App\RequestInterface $request;
    private \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository;
    private \MageOS\NetSuiteConnector\CustomerImport\Model\Config\CustomerImportConfig $customerImportConfig;
    private \Magento\Framework\UrlInterface $urlModel;

    /**
     * MessageManagerInterfacePlugin constructor.
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \MageOS\NetSuiteConnector\CustomerImport\Model\Config\CustomerImportConfig $customerImportConfig
     * @param \Magento\Framework\UrlInterface $urlModel
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \MageOS\NetSuiteConnector\CustomerImport\Model\Config\CustomerImportConfig $customerImportConfig,
        \Magento\Framework\UrlInterface $urlModel
    ) {

        $this->request = $request;
        $this->customerRepository = $customerRepository;
        $this->customerImportConfig = $customerImportConfig;
        $this->urlModel = $urlModel;
    }

    /**
     * @param ManagerInterface $messageManager
     * @param callable $proceed
     * @param $message
     * @param null $group
     * @return ManagerInterface
     */
    public function aroundAddErrorMessage(
        ManagerInterface $messageManager,
        callable $proceed,
        $message,
        $group = null
    ) {
        $triggerMessage = __('The account sign-in was incorrect or your account is disabled temporarily. '
            . 'Please wait and try again later.');
        /** @var \Magento\Framework\Phrase $message */
        if ($message == $triggerMessage && $this->request->isPost() && $this->customerImportConfig->getLoginMessage()) {
            $login = $this->request->getPost('login');
            if (isset($login['username'])) {
                $customMessage = $this->addCustomMessage(
                    $messageManager,
                    $login['username'],
                    'customerLoginButPasswordNotSet'
                );
                if ($customMessage) {
                    return $customMessage;
                }
            }
        }

        return $proceed($message, $group);
    }

    /**
     * @param ManagerInterface $messageManager
     * @param callable $proceed
     * @param $identifier
     * @param array $data
     * @param null $group
     * @return ManagerInterface
     */
    public function aroundAddComplexErrorMessage(
        ManagerInterface $messageManager,
        callable $proceed,
        $identifier,
        array $data = [],
        $group = null
    ): ManagerInterface {
        if ($identifier == 'customerAlreadyExistsErrorMessage'
            && $this->request->isPost()
            && $this->customerImportConfig->getRegistrationMessage()
        ) {
            $email = $this->request->getPost('email', false);
            if ($email !== false) {
                $customMessage = $this->addCustomMessage(
                    $messageManager,
                    $email,
                    'customerRegistrationButPasswordNotSet'
                );
                if ($customMessage) {
                    return $customMessage;
                }
            }
        }

        return $proceed($identifier, $data, $group);
    }

    /**
     * This generates a Warning Message with our custom Message notifying them that account was imported
     * and they should use "forgot password" feature to get access.
     *
     * @param ManagerInterface $messageManager
     * @param string $email
     * @param string $identifier
     * @return ManagerInterface|null,
     */
    private function addCustomMessage(
        ManagerInterface $messageManager,
        string $email,
        string $identifier
    ): ?ManagerInterface {
        try {
            $customer = $this->customerRepository->get($email);
            if ($customer->getCustomAttribute('imported_pwd_not_set')) {
                return $messageManager->addComplexNoticeMessage(
                    $identifier,
                    [
                        'url' => $this->urlModel->getUrl('customer/account/forgotpassword'),
                    ]
                );
            }
            // phpcs:disable
        } catch (\Exception $e) {
            // If something went wrong, just ignore and play along. This is frontend!
        }
        // phpcs:enable

        return null;
    }
}
