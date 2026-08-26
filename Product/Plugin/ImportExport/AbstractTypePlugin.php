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

namespace MageOS\NetSuiteConnector\Product\Plugin\ImportExport;

use Magento\CatalogImportExport\Model\Import\Product\Type\AbstractType;

class AbstractTypePlugin
{
    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\Plugin\ImportExport\PluginState
     */
    private $state;

    /**
     * AbstractTypePlugin constructor.
     * @param \MageOS\NetSuiteConnector\Core\Model\Plugin\ImportExport\PluginState $state
     */
    public function __construct(\MageOS\NetSuiteConnector\Core\Model\Plugin\ImportExport\PluginState $state)
    {
        $this->state = $state;
    }

    /**
     * DON'T clear empty columns in the Row Data
     *
     * @param array $rowData
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundClearEmptyData(
        AbstractType $subject,
        callable $proceed,
        array $rowData
    ) {
        if ($this->state->isRunning()) {
            return $rowData;
        }

        return $proceed($rowData);
    }
}
