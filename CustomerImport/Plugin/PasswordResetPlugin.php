<?php declare(strict_types=1);

namespace MageOS\NetSuiteConnector\CustomerImport\Plugin;

/**
 * Class PasswordResetPlugin - plugin add logic for password not set on import
 */
class PasswordResetPlugin
{
    private \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository;

    /**
     * PasswordResetPlugin constructor.
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    ) {
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param \Magento\Customer\Model\AccountManagement $subject
     * @param callable $proceed
     * @param $email
     * @param $resetToken
     * @param $newPassword
     * @return mixed
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\State\InputMismatchException
     *
     * @suppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundResetPassword(
        \Magento\Customer\Model\AccountManagement $subject,
        callable $proceed,
        $email,
        $resetToken,
        $newPassword
    ) {
        $result = $proceed($email, $resetToken, $newPassword);

        $customer = $this->customerRepository->get($email);
        if ($customer->getCustomAttribute('imported_pwd_not_set')) {
            $customer->setCustomAttribute('imported_pwd_not_set', false);
        }

        $this->customerRepository->save($customer);

        return $result;
    }
}
