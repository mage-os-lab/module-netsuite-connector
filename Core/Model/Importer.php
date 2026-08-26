<?php
/**
 * Importer
 *
 * @copyright Copyright © 2017 RocketWeb. All rights reserved.
 * @author    stan.smovdorenko@rocketweb.com
 */

namespace MageOS\NetSuiteConnector\Core\Model;

use Magento\AdvancedPricingImportExport\Model\Import\AdvancedPricing;
use MageOS\NetSuiteConnector\Core\Model\ImportExport\ArrayAdapterFactory;

use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterfaceFactory;
use Magento\ImportExport\Model\ImportFactory;

class Importer
{
    private const ALLOWED_ERRORS = 100;

    /** @var  string */
    protected $entityCode;

    /**
     * @var ImportFactory
     */
    private $importFactory;
    /**
     * @var ProcessingErrorAggregatorInterfaceFactory
     */
    private $errorAggregatorFactory;
    /**
     * @var ArrayAdapterFactory
     */
    private $adapterFactory;
    /**
     * @var ProcessingErrorAggregatorInterface
     */
    private $errorAggregator;
    /**
     * @var \Magento\ImportExport\Model\Import
     */
    private $importModel;

    /**
     * @var \Magento\ImportExport\Model\Import[]
     */
    private $importModels = [];

    /**
     * @var bool
     */
    protected $validationRequired = true;

    /**
     * Importer constructor.
     * @param ImportFactory $importFactory
     * @param ProcessingErrorAggregatorInterfaceFactory $errorAggregatorFactory
     * @param ArrayAdapterFactory $adapterFactory
     */
    public function __construct(
        ImportFactory $importFactory,
        ProcessingErrorAggregatorInterfaceFactory $errorAggregatorFactory,
        ArrayAdapterFactory $adapterFactory
    ) {
        $this->importFactory = $importFactory;
        $this->errorAggregatorFactory = $errorAggregatorFactory;
        $this->adapterFactory = $adapterFactory;
    }

    /**
     * @param string $entityCode
     */
    public function setEntityCode(string $entityCode)
    {
        $this->entityCode = $entityCode;
    }

    /**
     * @return string
     */
    public function getEntityCode(): string
    {
        return $this->entityCode;
    }

    /**
     * @param array $rows
     * @param string $behavior
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validateData(array $rows, $behavior = Import::BEHAVIOR_ADD_UPDATE): bool
    {
        if (empty($this->entityCode)) {
            return false;
        }

        if (!$rows) {
            return true;
        }

        foreach ($rows as $row) {
            if (!\is_array($row)) {
                return false;
            }
        }

        $importModel = $this->getImportModel($behavior);
        $arrayAdapter = $this->adapterFactory->create(['data' => $rows]);
        $result = $importModel->validateSource($arrayAdapter);
        $this->errorAggregator = $importModel->getErrorAggregator();

        return $result;
    }

    /**
     * @param $rows
     * @return ProcessingErrorAggregatorInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function importData($rows): ProcessingErrorAggregatorInterface
    {
        if (!$rows) {
            return $this->getEmptyErrorAggregator();
        }

        $importModel = $this->getImportModel();
        $importData = $this->validationRequired ? $this->validateData($rows) : true;

        if ($importData) {
            $importModel->importSource();
        }

        $this->errorAggregator = $importModel->getErrorAggregator();

        return $this->errorAggregator;
    }

    /**
     * @param $rows
     * @return ProcessingErrorAggregatorInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteData($rows): ProcessingErrorAggregatorInterface
    {
        if (!$rows) {
            return $this->getEmptyErrorAggregator();
        }

        $behavior = Import::BEHAVIOR_DELETE;
        $importModel = $this->getImportModel($behavior);

        if ($this->validateData($rows, $behavior)) {
            $importModel->importSource();
        }

        $this->errorAggregator = $importModel->getErrorAggregator();
        $this->importModel = null;

        return $this->errorAggregator;
    }

    /**
     * @param string $behavior
     * @return Import
     */
    private function getImportModel($behavior = Import::BEHAVIOR_ADD_UPDATE): Import
    {
        if (isset($this->importModels[$this->entityCode])) {
            return $this->importModels[$this->entityCode];
        }

        $importModel = $this->importFactory->create();
        $behavior = $this->entityCode == AdvancedPricing::ENTITY_TYPE_CODE ?
            Import::BEHAVIOR_REPLACE : $behavior;
        $importModel->setData([
            'entity' => $this->entityCode,
            'behavior' => $behavior,
            Import::FIELD_NAME_VALIDATION_STRATEGY =>
                ProcessingErrorAggregatorInterface::VALIDATION_STRATEGY_SKIP_ERRORS,
            'allowed_error_count' => self::ALLOWED_ERRORS,
            '_import_multiple_value_separator' => Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR,
            'ignore_duplicates' => '1'
        ]);

        $this->importModels[$this->entityCode] = $importModel;

        return $this->importModels[$this->entityCode];
    }

    /**
     * @return ProcessingErrorAggregatorInterface
     */
    private function getEmptyErrorAggregator(): ProcessingErrorAggregatorInterface
    {
        /** @var ProcessingErrorAggregatorInterface $aggregator */
        $aggregator = $this->errorAggregatorFactory->create();
        $aggregator->clear();
        return $aggregator;
    }

    /**
     * @return ProcessingErrorAggregatorInterface
     */
    public function getErrorAggregator(): ProcessingErrorAggregatorInterface
    {
        return $this->errorAggregator;
    }

    /**
     * @param bool $validationRequired
     */
    public function setValidationRequired(bool $validationRequired): void
    {
        $this->validationRequired = $validationRequired;
    }
}
