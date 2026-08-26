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

namespace MageOS\NetSuiteConnector\Product\Model\Prefetch;

use Magento\Framework\DataObject;

interface PrefetchIdSourceInterface
{
    /**
     * @param \NetSuite\Classes\Record[] $records
     * @return mixed
     */
    public function execute(array $records);

    /**
     * This method will receive
     * @param string $internalId
     * @param DataObject $product
     * @return mixed
     */
    public function mapToProduct(string $internalId, DataObject $product);

    /**
     * Cleanup caches
     */
    public function cleanup();
}
