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

namespace MageOS\NetSuiteConnector\Queue\Model\ResourceModel\Monitor\MonitorItem;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection implements \MageOS\NetSuiteConnector\Core\Api\MonitorItemCollectionInterface
{
    /**
     * @var string
     */
    protected $_idFieldName = 'monitor_id';

    protected function _construct(): void
    {
        $this->_init(
            \MageOS\NetSuiteConnector\Queue\Model\Monitor\MonitorItem::class,
            \MageOS\NetSuiteConnector\Queue\Model\ResourceModel\Monitor\MonitorItem::class
        );
    }

    protected function _afterLoad()
    {
        parent::_afterLoad();

        foreach ($this->_items as $item) {
            $item->afterLoad();
        }
    }
}
