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

namespace MageOS\NetSuiteConnector\CustomerImport\Plugin;

use Magento\Customer\Api\Data\CustomerInterface;
use NetSuite\Classes\BooleanCustomFieldRef;
use NetSuite\Classes\Customer as NetSuiteCustomer;
use NetSuite\Classes\CustomFieldList;
use MageOS\NetSuiteConnector\Customer\Model\Mapper\Customer;

/**
 * Class IsImportableSetPlugin - adds custom field to Customer Entity on customer export
 */
class IsImportableSetPlugin
{
    private \MageOS\NetSuiteConnector\CustomerImport\Model\Config\CustomerImportConfig $customerImportConfig;

    /**
     * IsImportableSetPlugin constructor.
     * @param \MageOS\NetSuiteConnector\CustomerImport\Model\Config\CustomerImportConfig $customerImportConfig
     */
    public function __construct(
        \MageOS\NetSuiteConnector\CustomerImport\Model\Config\CustomerImportConfig $customerImportConfig
    ) {
        $this->customerImportConfig = $customerImportConfig;
    }

    /**
     * we addd custom field isImportable for Customer export if such field configured
     * @param Customer $subject
     * @param NetSuiteCustomer $netSuiteCustomer
     * @param CustomerInterface $magentoCustomer
     * @return NetSuiteCustomer
     * @suppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetNetsuiteFormat(
        Customer $subject,
        NetSuiteCustomer $netSuiteCustomer,
        CustomerInterface $magentoCustomer
    ): NetSuiteCustomer {
        //if we have is importable custom field configured we need it be set to true on Company export
        $customFieldScriptId = $this->customerImportConfig->getIsImportableFieldId();
        if (!empty($customFieldScriptId)) {
            $customField = new BooleanCustomFieldRef();
            $customField->scriptId = $customFieldScriptId;
            $customField->value = true;
            if ($netSuiteCustomer->customFieldList === null) {
                $netSuiteCustomer->customFieldList = new CustomFieldList();
                $netSuiteCustomer->customFieldList->customField = [];
            }
            $netSuiteCustomer->customFieldList->customField[] = $customField;
        }
        return $netSuiteCustomer;
    }
}
