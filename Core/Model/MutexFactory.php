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

namespace MageOS\NetSuiteConnector\Core\Model;

use Magento\Framework\ObjectManagerInterface;
use MageOS\NetSuiteConnector\Core\Model\Mutex;

/**
 * Class MutexFactory - class responsible for mutex object creation and its name building
 */
class MutexFactory
{
    private const QUEUE_PREFIX = 'netsuite_queue_lock_';
    /**
     * Object Manager instance
     *
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var array
     */
    private $mutexPool = [];

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * Create RocketWeb Mutex instance or return if existed by given name
     *
     * @param array $modes
     * @return \MageOS\NetSuiteConnector\Core\Model\Mutex
     */
    public function createQueueMutex(array $modes): Mutex
    {
        $name = $this->prepareNameQueueMutex($modes);

        return $this->createWithName($name);
    }

    /**
     * @param array $modes
     * @return string
     */
    private function prepareNameQueueMutex(array $modes): string
    {
        return self::QUEUE_PREFIX . implode('_', $modes);
    }

    /**
     * Create RocketWeb Mutex instance or return if existed by given name
     *
     * @param string $name
     * @return \MageOS\NetSuiteConnector\Core\Model\Mutex
     */
    public function createWithName(string $name): Mutex
    {
        if (!empty($this->mutexPool[$name])) {
            return $this->mutexPool[$name];
        }

        $mutex = $this->objectManager->create(Mutex::class, ['lockName'=>$name]);
        $this->mutexPool[$name] = $mutex;
        return $mutex;
    }
}
