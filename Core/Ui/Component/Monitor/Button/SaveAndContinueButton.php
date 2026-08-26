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

class SaveAndContinueButton implements ButtonProviderInterface
{
    private \MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry $moduleRegistry;

    public function __construct(
        \MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry $moduleRegistry
    ) {
        $this->moduleRegistry = $moduleRegistry;
    }

    public function getButtonData(): array
    {
        /** @var MonitorItemInterface $monitorItem */
        $monitorItem = $this->moduleRegistry->registry('current_monitor_item');

        if (!$monitorItem->getHasPayload()
            || $monitorItem->hasStatus(Status::CANCELLED())
            || $monitorItem->hasStatus(Status::IN_PROGRESS())
            || $monitorItem->hasStatus(Status::DONE())
        ) {
            return [];
        }

        return [
            'label' => __('Save and Continue'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => ['button' => ['event' => 'saveAndContinueEdit']],
                'form-role' => 'save',
            ],
            'sort_order' => 40,
        ];
    }
}
