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
namespace MageOS\NetSuiteConnector\Inventory\Model\Process\Export;

/**
 * Backward compatible name for the handler moved in 103.0.6.
 *
 * Extending this class or resolving it through the object manager keeps working. Plugins and
 * preferences declared against this name do not apply, because the module wires the new name.
 * Re-point them at \MageOS\NetSuiteConnector\Inventory\Service\PostProcess\CompensateReservation.
 *
 * @deprecated Use \MageOS\NetSuiteConnector\Inventory\Service\PostProcess\CompensateReservation
 * @see \MageOS\NetSuiteConnector\Inventory\Service\PostProcess\CompensateReservation
 */
class CompensateReservation extends \MageOS\NetSuiteConnector\Inventory\Service\PostProcess\CompensateReservation
{
}
