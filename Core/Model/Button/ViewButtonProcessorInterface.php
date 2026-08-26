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
 *
 */

namespace MageOS\NetSuiteConnector\Core\Model\Button;

/**
 * Interface ViewButtonProcessorInterface responsible for adding buttons to edit page with link to NEtSuite
 */
interface ViewButtonProcessorInterface
{
    /**
     * checks is specific button belongs to block
     * @param \Magento\Framework\View\Element\AbstractBlock $block
     * @return bool
     */
    public function belongsToBlock(\Magento\Framework\View\Element\AbstractBlock $block): bool;

    /**
     * adds button with link information to block
     * @param \Magento\Backend\Block\Widget\Button\ButtonList $buttonList
     * @param string $netsuiteBaseUrl
     */
    public function addButton(
        \Magento\Backend\Block\Widget\Button\ButtonList $buttonList,
        string $netsuiteBaseUrl
    ): void;
}
