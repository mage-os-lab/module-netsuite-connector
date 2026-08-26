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

namespace MageOS\NetSuiteConnector\Core\Ui\Component\Monitor\Button;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use MageOS\NetSuiteConnector\Core\Api\Data\MonitorItemInterface;
use MageOS\NetSuiteConnector\Core\Model\Monitor\Data\Status;

class CancelButton implements ButtonProviderInterface
{
    private const URL = 'netsuite/monitor/cancel';

    private \Magento\Framework\UrlInterface $url;
    private \MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry $moduleRegistry;

    public function __construct(
        \Magento\Framework\UrlInterface $url,
        \MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry $moduleRegistry
    ) {
        $this->url = $url;
        $this->moduleRegistry = $moduleRegistry;
    }

    public function getButtonData(): array
    {
        /** @var MonitorItemInterface $monitorItem */
        $monitorItem = $this->moduleRegistry->registry('current_monitor_item');

        if (!$monitorItem->hasStatus(Status::IN_QUEUE())) {
            return [];
        }

        $url = $this->url->getUrl(self::URL, ['id' => $monitorItem->getId()]);

        return [
            'label' => __('Cancel'),
            'on_click' => 'deleteConfirm(\'Cancelling is irreversible. Are you sure you want to do this?\', \'' .
                $url . '\')',
            'class' => 'delete',
            'sort_order' => 20
        ];
    }
}
