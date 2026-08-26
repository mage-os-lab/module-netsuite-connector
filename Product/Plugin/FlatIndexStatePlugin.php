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

namespace MageOS\NetSuiteConnector\Product\Plugin;

use MageOS\NetSuiteConnector\Core\Model\FlatIndexState;

/**
 * Plugin for Abstract Flat State to manage flat index enable/disable state.
 */
class FlatIndexStatePlugin
{
    /**
     * @var FlatIndexState
     */
    private $indexState;

    /**
     * FlatIndexStatePlugin constructor.
     * @param FlatIndexState $indexState
     */
    public function __construct(FlatIndexState $indexState)
    {
        $this->indexState = $indexState;
    }

    /**
     * @param \Magento\Catalog\Model\Indexer\AbstractFlatState $subject
     * @param \Closure $proceed
     *
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundIsAvailable(
        \Magento\Catalog\Model\Indexer\AbstractFlatState $subject,
        \Closure $proceed
    ) {
        return $this->indexState->isDisabled() ? false : $proceed();
    }
}
