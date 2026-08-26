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

namespace MageOS\NetSuiteConnector\Core\Exception;

/**
 * Use this exception for any Data-related issues (NetSuite or Magento related). So if
 * there is a field missing or has missing data, throw this
 */
class DataIntegrityException extends \Exception
{
    public function __construct($message, $code = 0, ?\Throwable $previous = null)
    {
        if (is_array($message)) {
            $message = var_export($message, true);
        }

        parent::__construct($message, $code, $previous);
    }
}
