<?php

namespace MageOS\NetSuiteConnector\Core\Test\Integration\Model;

use NetSuite\Classes\BooleanCustomFieldRef;
use NetSuite\Classes\CreditMemoItem;
use NetSuite\Classes\CreditMemoItemList;
use NetSuite\Classes\CustomFieldList;
use NetSuite\Classes\InventoryAdjustment;
use NetSuite\Classes\InventoryAdjustmentInventory;
use NetSuite\Classes\InventoryAdjustmentInventoryList;
use NetSuite\Classes\InventoryItem;
use NetSuite\Classes\ItemMatrixType;
use NetSuite\Classes\ListOrRecordRef;
use NetSuite\Classes\MatrixOptionList;
use NetSuite\Classes\MultiSelectCustomFieldRef;
use NetSuite\Classes\Price;
use NetSuite\Classes\PriceList;
use NetSuite\Classes\Pricing;
use NetSuite\Classes\PricingMatrix;
use NetSuite\Classes\Record;
use NetSuite\Classes\RecordRef;
use NetSuite\Classes\ReturnAuthorizationItem;
use NetSuite\Classes\ReturnAuthorizationItemList;
use NetSuite\Classes\SelectCustomFieldRef;
use NetSuite\Classes\StringCustomFieldRef;
use NetSuite\Classes\RecordType;

/**
 * Class NSRecordBuilder
 * @package MageOS\NetSuiteConnector\Core\Test\Integration\Model
 * @method NSRecordBuilder withInternalId(mixed $value)
 * @method NSRecordBuilder withLocation(mixed $value)
 * @method NSRecordBuilder withLastModifiedDate(mixed $value)
 * @method NSRecordBuilder withStoreDisplayName(mixed $value)
 * @method NSRecordBuilder withItemId(mixed $value)
 * @method NSRecordBuilder withExternalId(mixed $value)
 * @method NSRecordBuilder withMatrixType(mixed $value)
 * @method NSRecordBuilder withTotal(mixed $value)
 * @method NSRecordBuilder withSubTotal(mixed $value)
 * @SuppressWarnings(PHPMD)
 */
class NSRecordBuilder
{
    private const TAX_INTERNAL_ID = 1;

    /**
     * @var Record
     */
    private $record;

    /**
     * NSRecordBuilder constructor.
     * @param Record $record
     */
    public function __construct(
        Record $record
    ) {
        $this->record = $record;
    }

    public function __clone()
    {
        $this->record = clone $this->record;
    }

    /**
     * @param $type
     * @return NSRecordBuilder
     */
    public static function aRecord($type): NSRecordBuilder
    {
        $record = new $type();

        return new self(
            $record
        );
    }

    /**
     * @param $externalId
     * @param $locationId
     * @return Record
     */
    public static function inventoryAdjustment(string $externalId, float $qty, string $locationId = '2'): Record
    {
        $inventory = new InventoryAdjustmentInventory();
        $inventory->item = new RecordRef();
        $inventory->item->externalId = $externalId;

        $inventory->location = new RecordRef();
        $inventory->location->internalId = $locationId;

        $inventory->adjustQtyBy = $qty;

        $ia = new InventoryAdjustment();
        $ia->subsidiary = new RecordRef();
        $ia->subsidiary->internalId = '1';
        $ia->account = new RecordRef();
        $ia->account->internalId = '1';
        $ia->inventoryList = new InventoryAdjustmentInventoryList();
        $ia->inventoryList->inventory = [$inventory];

        return $ia;
    }

    /**
     * @param string $itemId - itemId of this product
     * @param string $parentExtId - externalId of the parent product
     * @param array $matrixOpts - custom field which to use as matrix option
     * @param array $pricing - pricing matrix ['%price level id%' => [%qty:int%, %price:float%]]
     * @return Record
     */
    public static function matrixChild(
        string $itemId,
        string $parentExtId,
        array $matrixOpts,
        array $pricing
    ): Record {
        $mOptList = new MatrixOptionList();
        $mOptList->matrixOption = [];

        foreach ($matrixOpts as $scriptId => $value) {
            $listRef = new ListOrRecordRef();
            $listRef->internalId = $value;

            $optRef = new SelectCustomFieldRef();
            $optRef->scriptId = $scriptId;
            $optRef->value = $listRef;

            $mOptList->matrixOption[] = $optRef;
        }

        return self::aRecord(InventoryItem::class)
            ->withItemId($itemId)
            ->withTaxSchedule()
            ->withExternalId($itemId)
            ->withMatrixOptionList($mOptList)
            ->withParent('', $parentExtId)
            ->withMatrixType(ItemMatrixType::_child)
            ->pricing($pricing)
            ->build();
    }

    /**
     * @param $methodName
     * @param $args
     * @return NSRecordBuilder
     */
    public function __call($methodName, $args): NSRecordBuilder
    {
        if (strpos($methodName, 'with') === 0) {
            $propName = lcfirst(substr($methodName, 4));

            $builder = clone $this;
            $builder->record->$propName = $args[0];

            return $builder;
        }
    }

    /**
     * @param $fieldName
     * @param $fieldValue
     * @param null $listId
     * @return NSRecordBuilder
     */
    public function customField($fieldName, $fieldValue, $listId = null): NSRecordBuilder
    {
        $builder = clone $this;

        if (!$builder->record->customFieldList) {
            $builder->record->customFieldList = new CustomFieldList();
        }

        if ($listId) {
            $customField = new MultiSelectCustomFieldRef();
            $customField->scriptId = $fieldName;

            $customField->value = new ListOrRecordRef();
            $customField->value->name = $fieldValue;
            $customField->value->internalId = $fieldValue;
            $customField->value->typeId = $listId;

        } elseif (is_string($fieldValue)) {
            $customField = new StringCustomFieldRef();
            $customField->scriptId = $fieldName;
            $customField->value = $fieldValue;
        } elseif (is_bool($fieldValue)) {
            $customField = new BooleanCustomFieldRef();
            $customField->scriptId = $fieldName;
            $customField->value = $fieldValue;
        } else {
            $customField = new SelectCustomFieldRef();
            $customField->scriptId = $fieldName;
            // todo: this shouldn't work I guess
            $customField->value = $fieldValue;
        }

        $builder->record->customFieldList->customField[] = $customField;

        return $builder;
    }

    /**
     * @return Record
     */
    public function build(): Record
    {
        return $this->record;
    }

    /**
     * @param array $pricingData
     * @return NSRecordBuilder
     */
    public function pricing(array $pricingData): NSRecordBuilder
    {
        $builder = clone $this;

        $pricingMatrix = $builder->record->pricingMatrix;
        if (!$pricingMatrix) {
            $pricingMatrix = new PricingMatrix();
        }

        foreach ($pricingData as $priceLevelId => $entry) {
            $price = new Price();
            $price->quantity = $entry[0];
            $price->value = $entry[1];

            $pricing = new Pricing();
            $pricing->priceLevel = new RecordRef();
            $pricing->priceLevel->internalId = (string)$priceLevelId;
            $pricing->priceList = new PriceList();
            $pricing->priceList->price = [$price];
            $pricing->currency = new RecordRef();
            $pricing->currency->internalId = '1';

            $pricingMatrix->pricing[] = $pricing;
        }

        $builder->record->pricingMatrix = $pricingMatrix;

        return $builder;
    }

    /**
     * @param int $id
     * @param string $name
     * @return NSRecordBuilder
     */
    public function withCreatedFrom(int $id, string $name = ''): NSRecordBuilder
    {
        $builder = clone $this;

        $builder->record->createdFrom = new RecordRef();
        $builder->record->createdFrom->internalId = (string)$id;
        $builder->record->createdFrom->name = $name;

        return $builder;
    }

    /**
     * @param array[] ...$items
     * @return NSRecordBuilder
     */
    public function withRMAItemList(array ...$items): NSRecordBuilder
    {
        $builder = clone $this;

        $builder->record->itemList = new ReturnAuthorizationItemList();
        $builder->record->itemList->item = [];

        foreach ($items as $rmaItem) {
            $item = new ReturnAuthorizationItem();
            $item->item = new RecordRef();
            $item->item->internalId = (string)$rmaItem['id'];

            $item->customFieldList = new CustomFieldList();

            $customField = new StringCustomFieldRef();
            $customField->scriptId = $rmaItem['reason_field'];
            $customField->value = $rmaItem['reason_value'];

            $item->customFieldList->customField = [$customField];

            $builder->record->itemList->item[] = $item;
        }

        return $builder;
    }

    public function withCMItemList(array ...$items): NSRecordBuilder
    {
        $builder = clone $this;

        $builder->record->itemList = new CreditMemoItemList();
        $builder->record->itemList->item = [];

        foreach ($items as $cmItem) {
            $item = new CreditMemoItem();
            $item->item = new RecordRef();

            $item->item->internalId = (string)$cmItem['id'];
            $item->quantity = (float)$cmItem['qty'];

            $builder->record->itemList->item[] = $item;
        }

        return $builder;
    }

    public function withParent($internalId, $externalId = null): NSRecordBuilder
    {
        $builder = clone $this;

        $rref = new RecordRef();
        $rref->internalId = $internalId;
        $rref->externalId = $externalId;

        $builder->record->parent = $rref;

        return $builder;
    }

    public function withTaxSchedule(): NSRecordBuilder
    {
        $builder = clone $this;

        $taxSchedule = new RecordRef();
        $taxSchedule->internalId = self::TAX_INTERNAL_ID;
        $taxSchedule->type = RecordType::taxType;

        $builder->record->taxSchedule = $taxSchedule;

        return $builder;
    }

    public static function createRecordRef($internalId = '', $type = '', $externalId = '')
    {
        $recordRef = new RecordRef();
        if ($internalId != '') {
            $recordRef->internalId = (string)$internalId;
        }
        if ($type != '') {
            $recordRef->type = $type;
        }
        if ($externalId != '') {
            $recordRef->externalId = (string)$externalId;
        }

        return $recordRef;
    }
}
