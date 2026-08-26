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

namespace MageOS\NetSuiteConnector\Core\Model\Plugin;

use MageOS\NetSuiteConnector\Core\Exception\ConnectorRuntimeException;

/**
 * Class AddViewInNetSuiteButtons - add UI element to Store Admin
 */
class AddViewInNetSuiteButtons
{
    private array $buttonProcessors;
    private \MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig $connectorConfig;

    public function __construct(
        \MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig $connectorConfig,
        array $buttonProcessors = []
    ) {
        $this->buttonProcessors = $buttonProcessors;
        $this->connectorConfig = $connectorConfig;
    }

    public function beforePushButtons(
        \Magento\Backend\Block\Widget\Button\Toolbar $subject,
        \Magento\Framework\View\Element\AbstractBlock $context,
        \Magento\Backend\Block\Widget\Button\ButtonList $buttonList
    ) {
        foreach ($this->buttonProcessors as $buttonProcessor) {
            if (!($buttonProcessor instanceof \MageOS\NetSuiteConnector\Core\Model\Button\ViewButtonProcessorInterface)) {
                throw new ConnectorRuntimeException('Wrong Button Processor interface.');
            }
            if (!$buttonProcessor->belongsToBlock($context)) {
                continue;
            }
            $buttonProcessor->addButton($buttonList, $this->connectorConfig->getNetsuiteBaseUrl());
        }

        return [$context, $buttonList];
    }
}
