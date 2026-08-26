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

namespace MageOS\NetSuiteConnector\Core\Model\Message;

use MageOS\NetSuiteConnector\Core\Model\NetSuite\LastUpdateManager;

class ImportNotRun extends NetSuiteMessage
{
    protected $identity = 'NETSUITE_IMPORT_MESSAGE';

    public function getText()
    {
        return __("NetSuite import did not run in %1 hours", $this->developerConfig->getWarnIfImportNotRunAfter());
    }

    public function isDisplayed(): bool
    {
        $lastRunDate = $this->lastUpdateManager->getLastUpdateDate(LastUpdateManager::IMPORT_FLAG);
        if (!$lastRunDate) {
            return true;
        }
        $warnIfImportNotRun = $this->developerConfig->getWarnIfImportNotRunAfter();
        if (!$warnIfImportNotRun) {
            return false;
        }

        return time() > $this->getExpectedNextRunTimestamp(
            $warnIfImportNotRun,
            $lastRunDate
        );
    }
}
