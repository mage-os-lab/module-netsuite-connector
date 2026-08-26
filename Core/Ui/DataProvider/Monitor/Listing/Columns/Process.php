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

namespace MageOS\NetSuiteConnector\Core\Ui\DataProvider\Monitor\Listing\Columns;

use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\Data\OptionSourceInterface;
use MageOS\NetSuiteConnector\Core\Model\Monitor\Data\Process as ItemProcess;

class Process extends Column implements OptionSourceInterface
{
    public function prepareDataSource(array $dataSource)
    {
        $dataSource = parent::prepareDataSource($dataSource);

        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }
        $options = $this->getArrayData();

        foreach ($dataSource['data']['items'] as &$item) {
            if (isset($options[$item['process_original']])) {
                $item['process'] = $options[$item['process_original']];
            }
        }

        return $dataSource;
    }

    protected function getArrayData(): array
    {
        return ItemProcess::getLabels();
    }

    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->getArrayData() as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }

        return $options;
    }
}
