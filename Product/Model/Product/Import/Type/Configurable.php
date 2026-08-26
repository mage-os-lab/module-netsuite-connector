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
 *
 */

// @codingStandardsIgnoreFile
namespace MageOS\NetSuiteConnector\Product\Model\Product\Import\Type;

/**
 * This class extends the core class adds a getter for protected property to be used in plugin.
 * The way of extending with plugin chosen to make the rewrite more indepentent from core class
 *
 * @SuppressWarnings(PHPMD)
 */
class Configurable extends \Magento\ConfigurableImportExport\Model\Import\Product\Type\Configurable
{
    /**
     * Method returns superAttribute Data that will be used b
     * @return mixed
     */
    public function getSuperAttributeData()
    {
        return $this->_superAttributesData;
    }
}
