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

use Magento\Framework\Notification\MessageInterface;

abstract class NetSuiteMessage implements MessageInterface
{
    protected $identity = '';

    protected \MageOS\NetSuiteConnector\Core\Model\Config\DeveloperConfig $developerConfig;
    protected \MageOS\NetSuiteConnector\Core\Model\NetSuite\LastUpdateManager $lastUpdateManager;

    public function __construct(
        \MageOS\NetSuiteConnector\Core\Model\Config\DeveloperConfig $developerConfig,
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\LastUpdateManager $lastUpdateManager
    ) {

        $this->developerConfig = $developerConfig;
        $this->lastUpdateManager = $lastUpdateManager;
    }

    /**
     * Retrieve unique message identity
     *
     * @return string
     */
    public function getIdentity()
    {
        return md5($this->identity);// phpcs:ignore
    }

    /**
     * Retrieve message severity
     *
     * @return int
     */
    public function getSeverity()
    {
        return self::SEVERITY_MAJOR;
    }

    protected function getExpectedNextRunTimestamp(int $hours, $lastRunDate): int
    {
        //TODO: Refactor this to use proper \DateTime->creatFromFormat() and use of ->add()
        return strtotime(
            '+' .$hours . ' hours',
            strtotime($lastRunDate)
        );
    }
}
