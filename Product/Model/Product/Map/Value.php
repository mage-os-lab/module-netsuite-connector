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

declare(strict_types=1);

namespace MageOS\NetSuiteConnector\Product\Model\Product\Map;

use NetSuite\Classes\DateCustomFieldRef;
use NetSuite\Classes\ListOrRecordRef;

class Value
{
    public const FIELD_TYPE_STANDARD = 'standard_field';
    public const FIELD_TYPE_CUSTOM_SIMPLE = 'custom_simple';
    public const FIELD_TYPE_CUSTOM_LIST = 'custom_list';
    public const FIELD_TYPE_CUSTOM_CHECKBOX = 'custom_checkbox';
    public const FIELD_TYPE_CONSTANT_MAGENTO = 'public constant_magento_value';

    private const MAGENTO_CUSTOM_FIELD_NAME = '@__custom__';

    protected $netsuiteFieldId = null;
    protected $magentoFieldId = null;
    protected $netsuiteFieldType = null;
    protected $netsuiteListInternalId = null;
    protected $defaultValues = [];
    /**
     * @var  \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    protected $values = [];
    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\NetSuite\ServiceRepository
     */
    private $serviceRepository;

    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\ServiceRepository $serviceRepository,
        $magentoFieldId,
        $netsuiteFieldId,
        $netsuiteFieldType,
        $netsuiteFieldValue,
        $netsuiteListInternalId
    ) {
        $this->netsuiteFieldId = $netsuiteFieldId;
        if (!$magentoFieldId) {
            $this->magentoFieldId = self::MAGENTO_CUSTOM_FIELD_NAME;
        } else {
            $this->magentoFieldId = $magentoFieldId;
        }
        $this->eventManager = $eventManager;

        $this->netsuiteFieldType = $netsuiteFieldType;
        $this->netsuiteListInternalId = $netsuiteListInternalId;
        $this->addDefaultValue($netsuiteFieldId, $netsuiteFieldValue);

        $fieldsTypes = [
            self::FIELD_TYPE_STANDARD,
            self::FIELD_TYPE_CUSTOM_LIST,
            self::FIELD_TYPE_CUSTOM_CHECKBOX,
            self::FIELD_TYPE_CUSTOM_SIMPLE,
            self::FIELD_TYPE_CONSTANT_MAGENTO
        ];

        if (!in_array($netsuiteFieldType, $fieldsTypes)) {
            // phpcs:ignore
            throw new \Exception("Invalid Model Product Map Value Type {$netsuiteFieldType}");
        }

        if ($netsuiteFieldType == self::FIELD_TYPE_CUSTOM_LIST && !$netsuiteListInternalId) {
            // phpcs:ignore
            throw new \Exception("custom_list field type needs a custom list internal id");
        }
        $this->serviceRepository = $serviceRepository;
    }

    public function extractValue($inventoryItem, $currentNetsuiteId, $replaceExiting = false, $ignoreNullValues = true)
    {
        if ($replaceExiting) {
            $this->values = [];
        }
        switch ($this->netsuiteFieldType) {
            case self::FIELD_TYPE_STANDARD:
                $value = $this->extractValueFromStandardField($inventoryItem, $currentNetsuiteId);
                break;
            case self::FIELD_TYPE_CUSTOM_SIMPLE:
                $value = $this->extractValueFromSimpleCustomField($inventoryItem, $currentNetsuiteId);
                break;
            case self::FIELD_TYPE_CUSTOM_LIST:
                $value = $this->extractValueFromListCustomField($inventoryItem, $currentNetsuiteId);
                break;
            case self::FIELD_TYPE_CUSTOM_CHECKBOX:
                $value = $this->extractValueFromCheckboxCustomField($inventoryItem, $currentNetsuiteId);
                break;
            default:
                $value = null;
                break;
        }

        if ($ignoreNullValues && $value === null) {
            return null;
        }

        if (is_array($value)) {
            $this->values = array_merge($this->values, array_values($value));
        } else {
            $this->values[] = $value;
        }
    }

    /**
     * @param string $value
     */
    public function setConstantMagentoValue($value)
    {
        $this->values[] = $value;
    }

    protected function extractValueFromStandardField($inventoryItem, $currentNetsuiteId)
    {
        $value = null;
        if (isset($inventoryItem->{$currentNetsuiteId})) {
            $value = $inventoryItem->{$currentNetsuiteId};
        }
        return $value;
    }

    protected function extractValueFromSimpleCustomField($inventoryItem, $currentNetsuiteId)
    {
        $fields = $this->getCustomFields($inventoryItem);

        foreach ($fields as $customField) {
            if ($customField->scriptId ===  $currentNetsuiteId) {
                if ($customField->value instanceof ListOrRecordRef) {
                    return $customField->value->name;
                }
                if ($customField instanceof DateCustomFieldRef) {
                    $value = new \DateTime($customField->value);
                    return $value->format('Y-m-d H:i:s');
                }
                return $customField->value;
            }
        }
        return null;
    }

    protected function extractValueFromListCustomField($inventoryItem, $currentNetsuiteId)
    {
        $fields = $this->getCustomFields($inventoryItem);
        foreach ($fields as $customField) {
            if ($customField->scriptId == $currentNetsuiteId) {
                if ($customField->value instanceof ListOrRecordRef) {
                    return $this->serviceRepository->getListValue(
                        $customField->value->typeId,
                        $customField->value->internalId
                    );
                } elseif (is_array($customField->value)) {
                    /**
                     * This shouldn't actually work! The Response is either ENUM (string/int/...) or NS class. So this
                     * if statment should never come in affect.
                     * I'm suspecting this is related to how we used Test fixtures and this got in because of it.
                     */
                    $values = [];
                    foreach ($customField->value as $listValue) {
                        $values[] = $this->serviceRepository->getListValue($listValue->typeId, $listValue->internalId);
                    }
                    return $values;
                } else {
                    return $customField->value;
                }
            }
        }
        return null;
    }

    protected function extractValueFromCheckboxCustomField($inventoryItem, $currentNetsuiteId)
    {
        $fields = $this->getCustomFields($inventoryItem);

        foreach ($fields as $customField) {
            if ($customField->scriptId ==  $currentNetsuiteId) {
                if ($customField->value) {
                    return $this->getDefaultValue($currentNetsuiteId);
                } else {
                    return null;
                }
            }
        }
        return null;
    }

    public function addDefaultValue($netsuiteId, $value)
    {
        $this->defaultValues[$netsuiteId] = $value;
    }
    public function getDefaultValue($netsuiteId)
    {
        return $this->defaultValues[$netsuiteId];
    }

    // phpcs:ignore
    public static function getCustomValueListKey($magentoFieldName)
    {
        static $counter = 0;

        if (trim((string)$magentoFieldName)) {
            return trim((string)$magentoFieldName);
        }
        $counter++;
        return self::MAGENTO_CUSTOM_FIELD_NAME . $counter;
    }

    protected function getCustomFields($inventoryItem)
    {
        $fields = [];

        if (isset($inventoryItem->customFieldList) && is_array($inventoryItem->customFieldList->customField)) {
            foreach ($inventoryItem->customFieldList->customField as $field) {
                $fields[]=$field;
            }
        }
        if (isset($inventoryItem->matrixOptionList) && is_array($inventoryItem->matrixOptionList->matrixOption)) {
            foreach ($inventoryItem->matrixOptionList->matrixOption as $field) {
                $fields[]=$field;
            }
        }

        return $fields;
    }

    public function getValue()
    {
        return $this->values;
    }
    public function getValues()
    {
        return $this->values;
    }
    public function setValues($value)
    {
        $this->values = $value;
    }
    public function setValue($value)
    {
        $this->values = $value;
    }

    public function getNetsuiteFieldId()
    {
        return $this->netsuiteFieldId;
    }

    public function getMagentoFieldId()
    {
        return $this->magentoFieldId;
    }
}
