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

namespace MageOS\NetSuiteConnector\Inventory\Plugin;

use MageOS\NetSuiteConnector\Core\Command\NetSuiteCron;

/**
 * Class NetSuiteCron run stock update.
 */
class NetSuiteCronPlugin
{
    private const ALL_MODES = 'all';
    public const STOCK_MODE = 'stock';

    private \MageOS\NetSuiteConnector\Inventory\Model\Process\Import\Stock $stockProcessor;

    /**
     * NetSuiteCronPlugin constructor.
     * @param \MageOS\NetSuiteConnector\Inventory\Model\Process\Import\Stock $stockProcessor
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Inventory\Model\Process\Import\Stock $stockProcessor
    ) {
        $this->stockProcessor = $stockProcessor;
    }

    /**
     * Run stock import after in case of specific mode (stock) OR for full mode (all)
     *
     * Add plugin on method NetSuiteCron::processMode, which process import/export operations. It receive param $mode
     * which specifies desired import/export operation.
     *
     * @param NetSuiteCron $subject
     * @param mixed $result
     * @param string $runMode
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterProcessMode(
        NetSuiteCron $subject,
        $result,
        $runMode
    ): void {
        if ($runMode == self::ALL_MODES || $runMode == self::STOCK_MODE) {
            $this->stockProcessor->process();
        }
    }
}
