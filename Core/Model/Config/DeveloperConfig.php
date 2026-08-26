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

namespace MageOS\NetSuiteConnector\Core\Model\Config;

/**
 * @method int getEnableApiLog
 * @method int getErrorlogLifetime
 * @method int getCleanApiCallLogAfter
 * @method int getExportQueueThreshold
 * @method int getImportQueueThreshold
 * @method int getImportRecordLimit
 * @method bool getMailErrors
 * @method string getEmail
 * @method string getSenderEmailIdentity
 * @method string getExportQueueThresholdEmailTemplate
 * @method string getImportQueueThresholdEmailTemplate
 * @method int getWarnIfExportNotRunAfter
 * @method int getWarnIfImportNotRunAfter
 * @method int getWarnIfStockNotRunAfter
 * @method string getLoggerLevel
 */
class DeveloperConfig extends AbstractConfig
{
    private const ENABLE_API_LOG = 'mageos_netsuite/developer/enable_api_log';

    private const ERRORLOG_LIFETIME = 'mageos_netsuite/developer/errorlog_lifetime';

    private const CLEAN_API_CALL_LOG_AFTER = 'mageos_netsuite/developer/clean_api_call_log_after';

    private const EXPORT_QUEUE_THRESHOLD = 'mageos_netsuite/developer/export_queue_threshold';

    private const IMPORT_QUEUE_THRESHOLD = 'mageos_netsuite/developer/import_queue_threshold';

    private const IMPORT_RECORD_LIMIT = 'mageos_netsuite/developer/import_record_limit';

    private const MAIL_ERRORS = 'mageos_netsuite/developer/mail_errors';

    private const EMAIL = 'mageos_netsuite/developer/email';

    private const SENDER_EMAIL_IDENTITY = 'mageos_netsuite/developer/sender_email_identity';

    private const EXPORT_QUEUE_THRESHOLD_EMAIL_TEMPLATE =
        'mageos_netsuite/developer/export_queue_threshold_email_template';

    private const IMPORT_QUEUE_THRESHOLD_EMAIL_TEMPLATE =
        'mageos_netsuite/developer/import_queue_threshold_email_template';

    private const EXPORT_QUEUE_THRESHOLD_WARN_IF_NOT_RUN = 'mageos_netsuite/developer/warn_if_export_not_run_after';

    private const IMPORT_QUEUE_THRESHOLD_WARN_IF_NOT_RUN = 'mageos_netsuite/developer/warn_if_import_not_run_after';

    private const STOCK_THRESHOLD_WARN_IF_NOT_RUN = 'mageos_netsuite/developer/warn_if_stock_not_run_after';

    private const LOGGER_LEVEL = 'mageos_netsuite/developer/logger_level';

    public function getOptionsMap(): array
    {
        return [
            self::ENABLE_API_LOG => 'int',
            self::ERRORLOG_LIFETIME => 'int',
            self::CLEAN_API_CALL_LOG_AFTER => 'int',
            self::EXPORT_QUEUE_THRESHOLD => 'int',
            self::IMPORT_QUEUE_THRESHOLD => 'int',
            self::IMPORT_RECORD_LIMIT => 'int',
            self::MAIL_ERRORS => 'bool',
            self::EMAIL => 'string',
            self::SENDER_EMAIL_IDENTITY => 'string',
            self::EXPORT_QUEUE_THRESHOLD_EMAIL_TEMPLATE => 'string',
            self::IMPORT_QUEUE_THRESHOLD_EMAIL_TEMPLATE => 'string',
            self::EXPORT_QUEUE_THRESHOLD_WARN_IF_NOT_RUN => 'int',
            self::IMPORT_QUEUE_THRESHOLD_WARN_IF_NOT_RUN => 'int',
            self::STOCK_THRESHOLD_WARN_IF_NOT_RUN => 'int',
            self::LOGGER_LEVEL => 'string'
        ];
    }
}
