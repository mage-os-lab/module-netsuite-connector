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

namespace MageOS\NetSuiteConnector\Core\Model\NetSuite;

/**
 * This class is a helper class to load & save LastUpdate flag for import of imporatble objects. It saves the data into
 * a Magento Flag
 */
class LastUpdateManager
{
    public const IMPORT_FLAG = 'last_import_queue_run_date';
    public const EXPORT_FLAG = 'last_export_queue_run_date';
    /**
     * @var \Magento\Framework\FlagManager
     */
    private $flagManager;

    public function __construct(
        \Magento\Framework\FlagManager $flagManager
    ) {
        $this->flagManager = $flagManager;
    }

    /**
     * We use magento flags feature to keep saved start date of the last import
     *
     * @param string $flagCode
     * @return string|int|float|bool|array|null
     */
    public function getLastUpdateDate(string $flagCode)
    {
        return $this->flagManager->getFlagData($flagCode);
    }

    /**
     * Save given date as the date of the last import
     *
     * @param string $netsuiteDateString
     */
    public function setLastUpdateDate($flagCode, $netsuiteDateString)
    {
        $this->flagManager->saveFlag(
            $flagCode,
            \MageOS\NetSuiteConnector\Core\Model\NetSuite\ConvertDate::fromNetSuiteToSql($netsuiteDateString)
        );
    }
}
