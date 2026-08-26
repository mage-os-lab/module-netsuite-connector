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
namespace MageOS\NetSuiteConnector\Core\Model\NetSuite;

/**
 * Class is used to transform an array received from JSN string into NetSuite object
 *
 * The method is based on vendor/ryanwinchester/netsuite-php/src/includes/functions.php::setFields()
 * Its cleaned up & errors are removed, but the general logic is the same.
 * Modifications:
 * - no errors being thrown
 * - comments removed & added
 * - typecast() method added & implemented
 * - SIMPLE_ENUMS added for typecast() usage
 */
class FromJSON
{
    private const SIMPLE_ENUMS = [
        'string',
        'boolean',
        'int',
        'float'
    ];

    // phpcs:ignore
    public static function transform($object, ?array $fieldArray = null): void
    {
        $classname = get_class($object);
        $paramTypesMap = $classname::$paramtypesmap;

        if (!isset($paramTypesMap)) {
            $paramTypesMap = [];
        }

        if ($fieldArray == null) {
            return;
        }

        foreach ($fieldArray as $fldName => $fldValue) {
            if ((($fldValue == "") && $fldValue !== false) || (is_array($fldValue) && empty(array_filter($fldValue)))) {
                continue;
            }

            if (!isset($paramTypesMap[$fldName])) {
                // RW modification
                // the value is not a valid class attribute, so if it's not an array, we just assign it.
                // Under PHP 8.2+ this raises E_DEPRECATED because the SDK class does not declare the
                // property. The payload may carry richer shapes than paramtypesmap describes, for
                // example FileSiteCategory fields on a SiteCategory[] list or InventoryItem fields
                // on an AssemblyItem, and connector code reads those dynamically, so the assignment
                // stays. The error suppression is a stopgap: PHP 9 turns this into an Error and the
                // unknown fields must move to a container this package declares before then.
                if (!is_array($fldValue)) {
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                    @$object->$fldName = $fldValue;
                }
                continue;
            }

            if ($fldValue === 'false') {
                $object->$fldName = false;
            } elseif (is_object($fldValue)) {
                if (str_ends_with($paramTypesMap[$fldName], "[]")) {
                    // Trying to assign an object into an array parameter
                    continue;
                }
                $object->$fldName = $fldValue;
            } elseif (is_array($fldValue) && \Netsuite\array_is_associative($fldValue)) {
                $class = 'NetSuite\\Classes\\' . $paramTypesMap[$fldName];
                $obj = new $class();
                self::transform($obj, $fldValue);
                $object->$fldName = $obj;
            } elseif (is_array($fldValue) && !\Netsuite\array_is_associative($fldValue)) {
                if (!str_ends_with($paramTypesMap[$fldName], "[]")) {
                    // the type is not an array, skipping this value
                    continue;
                }

                $values = [];
                foreach ($fldValue as $item) {
                    if (is_object($item)) {
                        $values[] = $item;
                    } elseif (in_array($paramTypesMap[$fldName], self::SIMPLE_ENUMS)) {
                        $values[] = self::typecast($item, $paramTypesMap[$fldName]);
                    } else {
                        // get the base type  - the string is of type <type>[]
                        $basetype = substr($paramTypesMap[$fldName], 0, -2);
                        $class = 'NetSuite\\Classes\\' . $basetype;
                        if ($basetype == 'CustomFieldRef' && array_key_exists('value', $item)) {
                            $class = self::resolveCustomFieldRefClass($item['value']);
                        }
                        $obj = new $class();
                        self::transform($obj, $item);
                        $values[] = $obj;
                    }
                }

                $object->$fldName = $values;
            } else {
                $object->$fldName = self::typecast($fldValue, $paramTypesMap[$fldName]);
            }
        }
    }

    /**
     * Pick the concrete CustomFieldRef SDK subclass that declares $value.
     *
     * The base \NetSuite\Classes\CustomFieldRef only declares internalId/scriptId. Under PHP 8.2+
     * assigning $value directly triggers a dynamic-property deprecation. The concrete subclasses
     * (Boolean/Date/Double/Long/String/Select/MultiSelect) all declare $value with the correct type.
     *
     * @param mixed $value
     * @return string fully qualified class name
     */
    private static function resolveCustomFieldRefClass($value): string
    {
        if (is_bool($value)) {
            return 'NetSuite\\Classes\\BooleanCustomFieldRef';
        }
        if (is_int($value) || is_float($value)) {
            return 'NetSuite\\Classes\\DoubleCustomFieldRef';
        }
        if (is_array($value)) {
            return \Netsuite\array_is_associative($value)
                ? 'NetSuite\\Classes\\SelectCustomFieldRef'
                : 'NetSuite\\Classes\\MultiSelectCustomFieldRef';
        }
        return 'NetSuite\\Classes\\StringCustomFieldRef';
    }

    // phpcs:ignore
    private static function typecast($value, $type)
    {
        switch ($type) {
            case 'string':
                return (string)$value;
            case 'int':
                return (int)$value;
            case 'boolean':
            case 'bool':
                return (bool)$value;
            case 'float':
                return (float)$value;
            default:
                return $value;
        }
    }
}
