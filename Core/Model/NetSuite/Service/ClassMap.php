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

namespace MageOS\NetSuiteConnector\Core\Model\NetSuite\Service;

use NetSuite\NetSuiteClient;

/**
 * The SDK's SOAP classmap with the connector's replacements
 *
 * NetSuiteClient merges client options over its defaults with array_merge, so a "classmap" option
 * replaces the whole map: this class starts from the SDK's own map and swaps single entries.
 */
class ClassMap
{
    /**
     * WSDL type name => class used instead of the SDK's
     */
    public const OVERRIDES = [
        'SiteCategory' => \MageOS\NetSuiteConnector\Core\Model\NetSuite\Classes\SiteCategory::class,
    ];

    /**
     * @var array<string, string>|null
     */
    private ?array $classMap = null;

    /**
     * Get the classmap to pass to the SOAP client
     *
     * @return array<string, string>
     */
    public function get(): array
    {
        if ($this->classMap === null) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction -- builds a path, no filesystem access
            $sdkClassMapFile = dirname((new \ReflectionClass(NetSuiteClient::class))->getFileName())
                . '/includes/classmap.php';
            // phpcs:ignore Magento2.Security.IncludeFile.FoundIncludeFile
            $this->classMap = array_merge(require $sdkClassMapFile, self::OVERRIDES);
        }

        return $this->classMap;
    }
}
