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
namespace MageOS\NetSuiteConnector\Core\Model\ImportQueue;

use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;

class EntityProcessor
{
    public function __construct(
        protected \Magento\Framework\App\ResourceConnection $resourceConnection,
        protected \MageOS\NetSuiteConnector\Core\Model\FlatIndexState $flatIndexState,
        protected \MageOS\NetSuiteConnector\Core\Model\ImportRowList $importRowList,
        protected \MageOS\NetSuiteConnector\Core\Model\Importer $importer,
        protected \MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger,
        /** Mapping from bundle SKUs to single products to duplicate tier pricing array */
        protected array $bundleSkus = []
    ) {
    }

    public function process(string $entity): array
    {
        $rows = $this->importRowList->getEntityRows($entity);
        $this->logger->addDebug(sprintf('Entity %s:', $entity) . var_export($rows, true));

        // Process rows
        if (count($rows) == 0) {
            return [];
        }

        for ($retry = 0; $retry < 10; $retry++) {
            $this->flatIndexState->disable();

            /** @var ProcessingErrorAggregatorInterface $errorAggregator */
            $errorAggregator = $this->importer->importData($rows);

            $this->flatIndexState->enable();

            $errors = $errorAggregator->getAllErrors();

            if (!empty($errors)) {
                $errorMsg = $errors[0]->getErrorDescription() ?? $errors[0]->getErrorMessage();
                if (strpos($errorMsg, 'try restarting transaction') !== false) {
                    $this->logger->addDebug('Restarting Import Transaction, sleeping...');
                    sleep($retry + 1);// phpcs:ignore
                    continue;
                }
            }
            break;
        }

        ///////////////////////////////////
        // Process any errors we've got //
        //////////////////////////////////
        list($idsWithErrors, $_) = $this->validateErrors($errors, $rows);
        unset($_);
        return $idsWithErrors;
    }

    /**
     * @param string $entity
     * @param array $data
     *
     * @SuppressWarnings("unused")
     */
    public function postProcess(string $entity, array $data = [])// phpcs:ignore
    {
        //
    }

    public function validateErrors(?array $errors, $rows): array
    {
        $idsWithErrors = [];
        $skusWithErrors = [];
        foreach ($errors as $error) {
            $row = $rows[$error->getRowNumber()] ?? null;
            $id = $row ? ($row['netsuite_internal_id'] ?? $row['sku']) : 'N/A';
            $errorMsg = sprintf('%s (%s): %s', $error->getErrorMessage(), $id, $error->getErrorDescription());

            $this->logger->addDebug($errorMsg);
            $idsWithErrors[$row['netsuite_internal_id'] ?? $row['sku']] = $errorMsg;
            $skusWithErrors[$row['sku']] = $errorMsg;
        }

        return [$idsWithErrors, $skusWithErrors];
    }
}
