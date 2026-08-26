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
namespace MageOS\NetSuiteConnector\Product\Model;

/**
 * This class exists to return the correct FieldName of Products. The OpenSource/Community is using "entity_id" while
 * the Cloud/Commerce/Enterprise is using "row_id" so we need to get the right field. Since this part of code
 * is used all over the place, it makes sens to have a simple static method capable of handling this.
 */
class EntityIdColumn
{
    // phpcs:ignore
    public static function get(): string
    {
        $fieldName = 'entity_id';
        if (class_exists(\Magento\Enterprise\Model\ProductMetadata::class)) {
            $fieldName = 'row_id';
        }

        return $fieldName;
    }
}
