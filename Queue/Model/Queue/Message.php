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
 */

namespace MageOS\NetSuiteConnector\Queue\Model\Queue;

use Magento\Framework\DataObject;
use MageOS\NetSuiteConnector\Core\Api\Data\MessageInterface;
use MageOS\NetSuiteConnector\Core\Enum\Message\Queue;

class Message extends DataObject implements MessageInterface
{
    public function getId(): ?int
    {
        return $this->getData('message_id') ? (int)$this->getData('message_id') : null;
    }

    public function getAction(): string
    {
        return $this->getData('action');
    }

    public function getItemId(): int
    {
        return (int)$this->getData('item_id');
    }

    public function getObject()
    {
        return $this->getData('body');
    }

    public function getQueue(): Queue
    {
        return new Queue($this->getData('queue'));
    }

    // phpcs:ignore
    public function setData($key, $value = null): void
    {
        parent::setData($key, $value);
    }
}
