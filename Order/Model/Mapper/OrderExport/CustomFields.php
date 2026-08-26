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

namespace MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport;

use Magento\Sales\Api\Data\OrderInterface;
use NetSuite\Classes\CustomFieldList;
use NetSuite\Classes\GetRequest;
use NetSuite\Classes\ListOrRecordRef;
use NetSuite\Classes\Record;
use NetSuite\Classes\RecordRef;
use NetSuite\Classes\RecordType;
use NetSuite\Classes\SalesOrder;
use NetSuite\Classes\SelectCustomFieldRef;
use NetSuite\Classes\StringCustomFieldRef;
use MageOS\NetSuiteConnector\Order\Model\CustomFields as CustomFieldTypes;

/**
 * This class adds custom fields to NS order. It can add custom fields as regular NS order fields
 * OR as customFieldList object inside NS order. This is used for a magento order export.
 */
class CustomFields
{
    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management
     */
    private $serviceManagement;

    /**
     * @var \MageOS\NetSuiteConnector\Order\Model\Config\SalesConfig
     */
    private $salesConfig;

    /**
     * @param \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement
     * @param \MageOS\NetSuiteConnector\Order\Model\Config\SalesConfig $salesConfig
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement,
        \MageOS\NetSuiteConnector\Order\Model\Config\SalesConfig $salesConfig
    ) {
        $this->serviceManagement = $serviceManagement;
        $this->salesConfig = $salesConfig;
    }

    /**
     * Add custom fields for NS order record from magento order
     *
     * @param SalesOrder $netsuiteOrder
     * @param OrderInterface $magentoOrder
     */
    public function addCustomFields(SalesOrder $netsuiteOrder, OrderInterface $magentoOrder)
    {
        $this->addTypeStandard($netsuiteOrder, $magentoOrder);
        $this->addTypeCustom($netsuiteOrder, $magentoOrder);
    }

    /**
     * Check whether custom fields are configured
     *
     * @return boolean
     */
    private function canAddCustomFields(): bool
    {
        $customFieldsConfig = $this->getCustomFieldsConfig();
        if (is_array($customFieldsConfig) && count($customFieldsConfig)) {
            return true;
        }
        return false;
    }

    /**
     * Add custom fields to NS order as standard fields of NS order
     *
     * @param SalesOrder $netsuiteOrder
     * @param OrderInterface $magentoOrder
     */
    private function addTypeStandard(SalesOrder $netsuiteOrder, OrderInterface $magentoOrder)
    {
        if (!$this->canAddCustomFields()) {
            return;
        }
        $customFieldsConfig = $this->getCustomFieldsConfig();
        foreach ($customFieldsConfig as $customFieldsConfigItem) {
            switch ($customFieldsConfigItem['netsuite_field_type']) {
                case CustomFieldTypes::TYPE_STANDARD:
                    $netsuiteOrder->{$customFieldsConfigItem['netsuite_field_name']} =
                        $this->getCustomFieldValueFromMagentoData(
                            $magentoOrder,
                            $customFieldsConfigItem['value_type'],
                            $customFieldsConfigItem['value']
                        );
                    break;
                case CustomFieldTypes::TYPE_STANDARD_RECORD_REF:
                    $this->addStandardRecordRef(
                        $netsuiteOrder,
                        $magentoOrder,
                        $customFieldsConfigItem['netsuite_field_name'],
                        $customFieldsConfigItem['value_type'],
                        $customFieldsConfigItem['value']
                    );
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * Add custom fields to NS order as custom fields
     *
     * @param SalesOrder $netsuiteOrder
     * @param OrderInterface $magentoOrder
     */
    private function addTypeCustom(SalesOrder $netsuiteOrder, OrderInterface $magentoOrder)
    {
        if (!$this->canAddCustomFields()) {
            return;
        }
        $customFields = [];
        $customFieldsConfig = $this->getCustomFieldsConfig();
        foreach ($customFieldsConfig as $customFieldsConfigItem) {
            switch ($customFieldsConfigItem['netsuite_field_type']) {
                case CustomFieldTypes::TYPE_LIST:
                    $customFields[] = $this->createListCustomField(
                        $magentoOrder,
                        $customFieldsConfigItem['netsuite_field_name'],
                        $customFieldsConfigItem['netsuite_list_internal_id'],
                        $customFieldsConfigItem['value_type'],
                        $customFieldsConfigItem['value']
                    );
                    break;
                case CustomFieldTypes::TYPE_SIMPLE:
                    $customFields[] = $this->createSimpleCustomField(
                        $magentoOrder,
                        $customFieldsConfigItem['netsuite_field_name'],
                        $customFieldsConfigItem['value_type'],
                        $customFieldsConfigItem['value']
                    );
                    break;
                default:
                    break;
            }
        }
        if ($netsuiteOrder->customFieldList === null) {
            $netsuiteOrder->customFieldList = new CustomFieldList();
            $netsuiteOrder->customFieldList->customField = [];
        }
        $netsuiteOrder->customFieldList->customField = array_merge(
            $netsuiteOrder->customFieldList->customField,
            $customFields
        );
    }

    /**
     * Add specific field to NS order
     *
     * @param SalesOrder $netsuiteOrder
     * @param OrderInterface $magentoOrder
     * @param string $customFieldName
     * @param string $customFieldValueType
     * @param string $customFieldValue
     */
    private function addStandardRecordRef(
        SalesOrder $netsuiteOrder,
        OrderInterface $magentoOrder,
        $customFieldName,
        $customFieldValueType,
        $customFieldValue
    ) {
        $recordRefField = new RecordRef();
        $recordRefField->internalId = $this->getCustomFieldValueFromMagentoData(
            $magentoOrder,
            $customFieldValueType,
            $customFieldValue
        );
        $netsuiteOrder->{$customFieldName} = $recordRefField;
    }

    /**
     * Get the order customFields mapping from the configuration
     *
     * @return array
     */
    private function getCustomFieldsConfig()
    {
        return $this->salesConfig->getCustomFieldsMapping();
    }

    /**
     * Get field value from magento order based on field type
     *
     * @param OrderInterface $magentoOrder
     * @param string $customFieldValueType
     * @param string $customFieldValue
     * @return mixed
     */
    private function getCustomFieldValueFromMagentoData(
        OrderInterface $magentoOrder,
        $customFieldValueType,
        $customFieldValue
    ) {
        switch ($customFieldValueType) {
            case CustomFieldTypes::VALUE_TYPE_ORDER_ATTRIBUTE:
                return $magentoOrder->getData($customFieldValue);
            case CustomFieldTypes::VALUE_TYPE_FIXED:
            default:
                return $customFieldValue;
        }
    }

    /**
     * Create NS custom field object of list type
     *
     * @param OrderInterface $magentoOrder
     * @param string $customFieldName
     * @param int $customFieldInternalId
     * @param string $customFieldValueType
     * @param string $customFieldValue
     * @return SelectCustomFieldRef
     */
    private function createListCustomField(
        OrderInterface $magentoOrder,
        $customFieldName,
        $customFieldInternalId,
        $customFieldValueType,
        $customFieldValue
    ): SelectCustomFieldRef {
        $customField = new SelectCustomFieldRef();
        $customField->scriptId = $customFieldName;

        $customList = $this->loadCustomList($customFieldInternalId);

        $recordRef = new ListOrRecordRef();
        $recordRef->typeId = $customList->internalId;
        foreach ($customList->customValueList->customValue as $customListCustomValue) {
            $value = $this->getCustomFieldValueFromMagentoData($magentoOrder, $customFieldValueType, $customFieldValue);
            if (strtolower($value) == strtolower($customListCustomValue->value)) {
                $recordRef->internalId = $customListCustomValue->valueId;
                break;
            }
        }

        $customField->value = $recordRef;

        return $customField;
    }

    /**
     * Create NS custom field object of simple type
     *
     * @param OrderInterface $magentoOrder
     * @param string $customFieldName
     * @param string $customFieldValueType
     * @param string $customFieldValue
     * @return StringCustomFieldRef
     */
    private function createSimpleCustomField(
        OrderInterface $magentoOrder,
        $customFieldName,
        $customFieldValueType,
        $customFieldValue
    ): StringCustomFieldRef {
        $customField = new StringCustomFieldRef();
        $customField->scriptId = $customFieldName;
        $customField->value = $this->getCustomFieldValueFromMagentoData(
            $magentoOrder,
            $customFieldValueType,
            $customFieldValue
        );

        return $customField;
    }

    /**
     * Request custom field data from NS
     *
     * @param int $internalId
     * @return Record
     * @throws \RuntimeException
     */
    private function loadCustomList($internalId): Record
    {
        $request = new GetRequest();
        $request->baseRef = new RecordRef();
        $request->baseRef->internalId = $internalId;
        $request->baseRef->type = RecordType::customList;

        // todo: could be improved with caching
        $getResponse = $this->serviceManagement->get()->get($request);
        if (!$getResponse->readResponse->status->isSuccess) {
            throw new \RuntimeException(var_export($getResponse->readResponse->status->statusDetail, true));
        } else {
            return $getResponse->readResponse->record;
        }
    }
}
