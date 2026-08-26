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
use MageOS\NetSuiteConnector\Core\Model\Monitor\Data\Process;

class EntityId extends Column
{
    public function prepareDataSource(array $dataSource)
    {
        $dataSource = parent::prepareDataSource($dataSource);

        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            $source = '';
            if ($item['process_original'] == (string)Process::IMPORT()) {
                $source = 'NetSuite ID';
            } elseif ($item['process_original'] == (string)Process::EXPORT()) {
                $source = 'Magento ID';
            }

            if ($source != '') {
                $item['item_id'] = sprintf('%s (%s)', $item['item_id_original'], $source);
            }
        }

        return $dataSource;
    }
}
