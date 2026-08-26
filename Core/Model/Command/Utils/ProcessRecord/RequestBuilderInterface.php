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

namespace MageOS\NetSuiteConnector\Core\Model\Command\Utils\ProcessRecord;

use NetSuite\Classes\SearchRequest;

/**
 * Interface RequestBuilderInterface
 * @Spi - service interface
 */
interface RequestBuilderInterface
{
    /**
     * method builds request to be used for record processor
     * @param array $recordIds
     * @return SearchRequest
     */
    public function getNetsuiteRequest(array $recordIds): SearchRequest;
}
