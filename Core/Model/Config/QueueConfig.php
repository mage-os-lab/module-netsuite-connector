<?php
/**
 * QueueConfig
 *
 * @copyright Copyright © 2018 RocketWeb. All rights reserved.
 * @author    stan.smovdorenko@rocketweb.com
 */

namespace MageOS\NetSuiteConnector\Core\Model\Config;

/**
 * @method bool getDeleteExistingItemsInImportQueue
 * @method int getImportBatchSize
 * @method int getUpdatedFromMinutes
 * @method int getExportBatchSize
 * @method int getTimeout
 */
class QueueConfig extends AbstractConfig
{
    private const DELETE_EXISTING_ITEMS_IN_IMPORT_QUEUE =
        'mageos_netsuite/queue_processing/delete_existing_items_in_import_queue';
    private const DELETE_EXISTING_ITEMS_IN_IMPORT_QUEUE_HOURS =
        'mageos_netsuite/queue_processing/delete_existing_items_in_import_queue_hours';

    private const IMPORT_BATCH_SIZE = 'mageos_netsuite/queue_processing/import_batch_size';

    private const UPDATED_FROM_MINUTES = 'mageos_netsuite/queue_processing/updated_from_minutes';

    private const EXPORT_BATCH_SIZE = 'mageos_netsuite/queue_processing/export_batch_size';
    private const TIMEOUT = 'mageos_netsuite/queue_processing/timeout';

    public function getOptionsMap(): array
    {
        return [
            self::DELETE_EXISTING_ITEMS_IN_IMPORT_QUEUE => 'bool',
            self::DELETE_EXISTING_ITEMS_IN_IMPORT_QUEUE_HOURS => 'int',
            self::IMPORT_BATCH_SIZE => 'int',
            self::UPDATED_FROM_MINUTES => 'int',
            self::EXPORT_BATCH_SIZE => 'int',
            self::TIMEOUT => 'int',
        ];
    }
}
