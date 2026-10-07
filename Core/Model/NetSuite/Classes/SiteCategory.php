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

namespace MageOS\NetSuiteConnector\Core\Model\NetSuite\Classes;

/**
 * SiteCategory that can also hold an item's web store category
 *
 * The WSDL declares two types named SiteCategory: the Site Category record (urn:website) and the
 * category entry of an item's siteCategoryList (urn:accounting, SiteCategory_accounting in the SDK).
 * SoapClient's classmap keys on the local type name only, so both are hydrated into the same class.
 * The SDK maps the name to the record, which lacks the item entry's fields, and PHP 8.2+ reports
 * "Creation of dynamic property" for each of them. Magento turns that deprecation into an exception,
 * so reading any item with web store categories failed. This class declares both shapes.
 */
class SiteCategory extends \NetSuite\Classes\SiteCategory
{
    /**
     * @var \NetSuite\Classes\RecordRef
     */
    public $category;

    /**
     * @var boolean
     */
    public $isDefault;

    /**
     * @var string
     */
    public $categoryDescription;
}
