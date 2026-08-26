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
namespace MageOS\NetSuiteConnector\Core\Model\Process;

use MageOS\NetSuiteConnector\Core\Model\Process\Export\AbstractExportProcessor;

class ExportProcessor extends AbstractProcessor
{
    /**
     * @deprecated This is not being used as we need the processor list for IMPORT process only.
     * @return array
     */
    public function getExportableEntities(): array
    {
        return $this->processors;
    }

    /**
     * @param $entityCode
     *
     * @return mixed
     */
    public function getEntityProcessor($entityCode): ?AbstractExportProcessor
    {
        $entityCode = $this->formatEntityCode($entityCode);
        if (!$this->validate($entityCode, AbstractExportProcessor::class)) {
            return null;
        }

        return $this->processors[$entityCode];
    }
}
