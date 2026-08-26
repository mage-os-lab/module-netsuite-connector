<?php

namespace MageOS\NetSuiteConnector\Core\Model;

class ImportRowList
{
    protected $attributeInfo;

    protected $entities = [];
    protected $productsInQueue = [];

    /**
     * @param string $entity
     * @param array $row
     */
    public function pushRowToEntity($entity, $row): void
    {
        if (!isset($this->entities[$entity])) {
            $this->entities[$entity] = [];
        }

        $netSuiteId = $row['netsuite_internal_id'] ?? null;

        if ($netSuiteId && isset($this->productsInQueue[$netSuiteId])) {
            $rowIdx = $this->productsInQueue[$netSuiteId];
            $this->entities[$entity][$rowIdx] = $row;
        } else {
            $this->entities[$entity][] = $row;

            if ($netSuiteId) {
                $this->productsInQueue[$netSuiteId] = count($this->entities[$entity]) - 1;
            }
        }
    }

    /**
     * @param ImportRowList $anotherInstance
     */
    public function mergeWith(ImportRowList $anotherInstance): void
    {
        foreach ($anotherInstance->entities as $key => $value) {
            $this->pushRowsToEntity($key, $value);
        }
    }

    /**
     * @param $entity
     * @param $data
     */
    public function pushRowsToEntity($entity, $data)
    {
        if (empty($data)) {
            return;
        }

        foreach ($data as $row) {
            $this->pushRowToEntity($entity, $row);
        }
    }

    /**
     * @param string $entityId
     * @return mixed
     */
    public function getRawEntityData($entityId)
    {
        return $this->entities[$entityId] ?? [];
    }

    /**
     * @param string $entityId
     * @param array $data
     */
    public function setRawEntityData($entityId, $data)
    {
        $this->entities[$entityId] = $data;
    }

    /**
     * Formats entity rows into the format used by import/export
     *
     * @param string $entity
     * @return array
     */
    public function getEntityRows(string $entity): array
    {
        if (!isset($this->entities[$entity]) || !is_array($this->entities[$entity])) {
            return [];
        }

        $result = [];
        foreach ($this->entities[$entity] as $row) {
            if (isset($row['_incomplete'])) {
                continue;
            }

            $resRow = [];

            foreach ($row as $key => $value) {
                if (is_array($value)) {
                    $resRow[$key] = $this->arrayToStringUnquoted($key, $value);
                } else {
                    $resRow[$key] = $value;
                }
            }

            $result[] = $resRow;
        }

        return $result;
    }

    public function setAttributeInfo($attributeInfo)
    {
        $this->attributeInfo = $attributeInfo;
    }

    /**
     * TODO: See if we can get more "exact" attribute data so we don't need to load all of them
     *
     * @param array $arrayIn
     * @return array|string
     */
    private function arrayToStringUnquoted($key, $arrayIn)
    {
        $res = [];
        $separator = ',';

        if ($key == 'custom_options') {
            uksort($arrayIn, function ($first, $second) {
                return strcmp($first, $second);
            });
        }

        foreach ($arrayIn as $code => $value) {
            if (is_object($value) && ($value->name)) {
                $value = $value->name;
            }
            if (is_array($value)) {
                $separator = '|';
                $value = $this->arrayToStringUnquoted($code, $value);
                $res[] = $value;
                continue;
            }

            //TODO: Add more inline comment on what is happening here.
            if (strpos((string)$value, ',') !== false) {
                // there should be only one value for super attributes
                if ($this->isSuperAttribute($code)) {
                    $res[] = $code . '=' . substr($value, 0, strpos($code, ','));
                    continue;
                }
                /**
                 * TODO: Not sure if this is correct explanation :D
                 * For text attributes we support adding multiple values together when mapping NS fields to
                 * Magento attribute.
                 */
                if ($this->isTextAttribute($code)) {
                    $res[] = $code . '=' . $value;
                } else {
                    $res[] = $code . '=' . str_replace(',', '|', $value);
                }
                continue;
            }

            $res[] = $code . '=' . $value;
        }

        $res = implode($separator, $res);

        return $res;
    }

    public function isSuperAttribute($attributeCode)
    {
        $attribute = $this->attributeInfo[$attributeCode] ?? null;
        return $attribute && $attribute['type'] === 'select' && $attribute['is_global'];
    }

    public function isTextAttribute($attributeCode)
    {
        $attribute = $this->attributeInfo[$attributeCode] ?? null;
        return $attribute && $attribute['type'] === 'text';
    }

    /**
     * Returns entities which are in this import list
     * @return string[]
     */
    public function getEntities()
    {
        return array_keys($this->entities);
    }

    public function clear()
    {
        $this->entities = [];
        $this->productsInQueue = [];
    }

    public function isProductInQueue($netSuiteId)
    {
        return isset($this->productsInQueue[$netSuiteId]);
    }

    public function resolveSku($netSuiteId)
    {
        $rowIdx = $this->productsInQueue[$netSuiteId] ?? null;
        return $rowIdx !== null ? $this->entities['catalog_product'][$rowIdx]['sku'] : null;
    }

    public function getProductRowById($netSuiteId)
    {
        $rowIdx = $this->productsInQueue[$netSuiteId] ?? null;
        return $rowIdx !== null ? $this->entities['catalog_product'][$rowIdx] : null;
    }
}
