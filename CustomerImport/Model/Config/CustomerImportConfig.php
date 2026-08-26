<?php
declare(strict_types=1);
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

namespace MageOS\NetSuiteConnector\CustomerImport\Model\Config;

use MageOS\NetSuiteConnector\Core\Model\Config\AbstractConfig;

/**
 * @method array getRequiredAddressFields
 * @method int getDefaultStoreId
 * @method int getDefaultCustomerGroup
 * @method string getDefaultAddressZip
 * @method string getDefaultAddressCity
 * @method string getDefaultAddressPhone
 * @method string getDefaultAddressStreet
 * @method string getDefaultAddressCountry
 * @method bool getLoginMessage
 * @method bool getRegistrationMessage
 * @method string getIsImportableFieldId
 */
class CustomerImportConfig extends AbstractConfig
{
    private const REQUIRED_ADDRESS_FIELDS = 'mageos_netsuite/customers_import/required_address_fields';
    private const DEFAULT_STORE_ID = 'mageos_netsuite/customers_import/default_store_id';
    private const DEFAULT_CUSTOMER_GROUP = 'mageos_netsuite/customers_import/default_customer_group';
    private const DEFAULT_ADDRESS_ZIP = 'mageos_netsuite/customers_import/default_address_zip';
    private const DEFAULT_ADDRESS_CITY = 'mageos_netsuite/customers_import/default_address_city';
    private const DEFAULT_ADDRESS_PHONE = 'mageos_netsuite/customers_import/default_address_phone';
    private const DEFAULT_ADDRESS_STREET = 'mageos_netsuite/customers_import/default_address_street';
    private const DEFAULT_ADDRESS_COUNTRY = 'mageos_netsuite/customers_import/default_address_country';
    private const LOGIN_MESSAGE = 'mageos_netsuite/customers_import/login_message';
    private const REGISTRATION_MESSAGE = 'mageos_netsuite/customers_import/registration_message';
    private const IS_IMPORTABLE_FIELD_ID = 'mageos_netsuite/customers_import/is_importable_field_id';

    public function getOptionsMap(): array
    {
        return [
            self::REQUIRED_ADDRESS_FIELDS => 'csv',
            self::DEFAULT_STORE_ID => 'int',
            self::DEFAULT_CUSTOMER_GROUP => 'int',
            self::DEFAULT_ADDRESS_CITY => 'string',
            self::DEFAULT_ADDRESS_PHONE => 'string',
            self::DEFAULT_ADDRESS_STREET => 'string',
            self::DEFAULT_ADDRESS_ZIP => 'string',
            self::DEFAULT_ADDRESS_COUNTRY => 'string',
            self::LOGIN_MESSAGE => 'bool',
            self::REGISTRATION_MESSAGE => 'bool',
            self::IS_IMPORTABLE_FIELD_ID => 'string'
        ];
    }
}
