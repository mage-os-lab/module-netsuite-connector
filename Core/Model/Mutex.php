<?php
declare(strict_types=1);

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
 *
 */

namespace MageOS\NetSuiteConnector\Core\Model;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Filesystem\DirectoryList;

class Mutex
{
    public const NETSUITE_TMP_DIR = 'netsuite';

    public $writeablePath = '';
    public $lockName = '';
    public $fileHandle = null;

    /**
     * Mutex constructor.
     * @param $lockName
     * @param null $writeablePath
     */
    public function __construct($lockName, $writeablePath = null)
    {
        $this->lockName = preg_replace('/[^a-z0-9]/', '', $lockName);
        if ($writeablePath == null) {
            $this->writeablePath = $this->findWriteablePath();
        }
    }

    /**
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getLock()
    {
        return flock($this->getFileHandle(), LOCK_EX | LOCK_NB);// phpcs:ignore
    }

    /**
     * @return bool|resource|null
     */
    public function getFileHandle()
    {
        if ($this->fileHandle == null) {
            $this->fileHandle = fopen($this->getLockFilePath(), 'c');// phpcs:ignore
        }
        return $this->fileHandle;
    }

    /**
     * @return bool
     */
    public function releaseLock()
    {
        $success = flock($this->getFileHandle(), LOCK_UN);// phpcs:ignore
        fclose($this->getFileHandle());// phpcs:ignore
        return $success;
    }

    /**
     * @return string
     */
    public function getLockFilePath()
    {
        return $this->writeablePath . DIRECTORY_SEPARATOR . $this->lockName;
    }

    /**
     * @return bool
     */
    public function isLocked()
    {
        $fileHandle = fopen($this->getLockFilePath(), 'c');// phpcs:ignore
        $canLock = flock($fileHandle, LOCK_EX | LOCK_NB);// phpcs:ignore
        if ($canLock) {
            flock($fileHandle, LOCK_UN);// phpcs:ignore
        }
        fclose($fileHandle);// phpcs:ignore
        return true;
    }

    /**
     * @return mixed|string
     */
    public function findWriteablePath()
    {
        $this->writeablePath = $this->getVarDir() . DIRECTORY_SEPARATOR . self::NETSUITE_TMP_DIR;
        if (!file_exists($this->writeablePath)) {// phpcs:ignore
            mkdir($this->writeablePath, 0777, true);// phpcs:ignore
        }
        return $this->writeablePath;
    }

    /**
     * done via object manager coz of class consructor legacy dependencies
     * @todo refactor what mutex class required
     */
    private function getVarDir()
    {
        $objectManager = ObjectManager::getInstance();
        $directory = $objectManager->get(DirectoryList::class);
        return $directory->getPath('var');
    }
}
