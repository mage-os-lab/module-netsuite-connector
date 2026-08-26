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

namespace MageOS\NetSuiteConnector\Core\Ui\DataProvider\Monitor\Form;

use MageOS\NetSuiteConnector\Core\Model\Monitor\Data\OutputLevel;
use MageOS\NetSuiteConnector\Core\Model\Monitor\Data\Process;
use MageOS\NetSuiteConnector\Core\Model\Monitor\Data\Status;

class FormDataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    private \MageOS\NetSuiteConnector\Core\Model\Config\MonitorConfig $monitorConfig;
    private \Magento\Framework\Stdlib\DateTime\Timezone $timezone;
    private \MageOS\NetSuiteConnector\Core\Model\Monitor\Data\Source\Entity $entitySource;

    public function __construct(
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \MageOS\NetSuiteConnector\Core\Model\Config\MonitorConfig $monitorConfig,
        \MageOS\NetSuiteConnector\Core\Model\Monitor\Data\Source\Entity $entitySource,
        \MageOS\NetSuiteConnector\Core\Api\MonitorItemCollectionInterfaceFactory $monitorItemCollectionFactory,
        $name,
        $primaryFieldName,
        $requestFieldName,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $monitorItemCollectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->monitorConfig = $monitorConfig;
        $this->timezone = $timezone;
        $this->entitySource = $entitySource;
    }

    public function getData()
    {
        $data = parent::getData();
        $entityLabels = $this->entitySource->toArray();

        foreach ($data['items'] as &$row) {
            $row['process'] = Process::getLabel(new Process($row['process']));
            $row['status'] = Status::getLabel(new Status($row['status']));
            $row['entity'] = $entityLabels[$row['entity']];
            $row['debug_level'] = $this->getDebugLevelLabel();
            $row['process_output'] = $this->renderProcessOutput($row['process_output_decoded']);
            $row['last_error'] = $this->renderLastProcessOutputError($row['process_output_decoded']);
            $createdAt = new \DateTime($row['created_at']);
            $row['created_at'] = $this->timezone->formatDateTime(
                $createdAt,
                \IntlDateFormatter::MEDIUM,
                \IntlDateFormatter::MEDIUM
            );
            $row['overwrite_payload'] = (bool)$row['overwrite_payload'];

            if (!empty($row['payload'])) {
                $row['payload'] = json_encode(json_decode($row['payload']), JSON_PRETTY_PRINT);
            }
        }

        return $data;
    }

    private function getDebugLevelLabel(): string
    {
        $labels = OutputLevel::getLabels();
        $debugLevel = $this->getDebugLevel();

        if (!isset($labels[$debugLevel])) {
            $debugLevel = (string)OutputLevel::STANDARD();
        }

        return $labels[$debugLevel];
    }

    private function getDebugLevel(): string
    {
        $debugLevel = 'standard';//$this->monitorConfig->getDebugLevel();
        return $debugLevel ?? (string)OutputLevel::DEBUG();
    }

    private function renderLastProcessOutputError(?array $data): string
    {
        if ($data === null) {
            return '';
        }

        $errors = [];
        foreach ($data as $row) {
            if (!is_array($row)) {
                continue;
            }

            $row = $this->prepareRow($row, (string)OutputLevel::STANDARD());
            if ($row === null) {
                continue;
            }
            list($datetime, $message) = $row;
            $errors[$datetime] = $message;
        }

        krsort($errors, SORT_NUMERIC);

        $lastOutput = array_shift($errors);
        return $lastOutput ?? '';
    }

    private function prepareRow(array $row, string $currentLevel): ?array
    {
        list($datetime, $message) = $row;
        $level = (string)OutputLevel::DEBUG();
        if (count($row) == 3) {
            list(, , $level) = $row;
        }

        if ($currentLevel == (string)OutputLevel::STANDARD()
            && $level == (string)OutputLevel::DEBUG()
        ) {
            /**
             * In output_level = standard, we skip all debug levels
             */
            return null;
        }

        return [$datetime, $message, $level];
    }

    private function renderProcessOutput(?array $data): string
    {
        if ($data === null) {
            return '';
        }

        $output = [];
        foreach ($data as $row) {
            if (!is_array($row)) {
                continue;
            }

            $row = $this->prepareRow($row, $this->getDebugLevel());
            if ($row === null) {
                continue;
            }
            list($datetime, $message) = $row;

            $timestamp = $this->timezone->formatDateTime(
                (new \DateTime())->setTimestamp($datetime),
                \IntlDateFormatter::MEDIUM,
                \IntlDateFormatter::MEDIUM
            );
            $output[] = sprintf('<b>%s</b>: %s', $timestamp, $message);
        }

        return implode('<br />', $output);
    }
}
