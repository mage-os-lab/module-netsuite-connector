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

namespace MageOS\NetSuiteConnector\Test\Unit\Core\Model\NetSuite\Service;

use MageOS\NetSuiteConnector\Core\Model\NetSuite\Classes\SiteCategory;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\ClassMap;
use NetSuite\Classes\SiteCategory_accounting;
use PHPUnit\Framework\TestCase;

class ClassMapTest extends TestCase
{
    /**
     * The option replaces the SDK's whole map, so every SDK entry must still be there
     */
    public function testItKeepsEveryEntryOfTheSdkClassMap(): void
    {
        $sdkMap = require dirname((new \ReflectionClass(\NetSuite\NetSuiteClient::class))->getFileName())
            . '/includes/classmap.php';

        $classMap = (new ClassMap())->get();

        $this->assertSame(array_keys($sdkMap), array_keys($classMap));
        foreach ($sdkMap as $type => $class) {
            if (!isset(ClassMap::OVERRIDES[$type])) {
                $this->assertSame($class, $classMap[$type], $type);
            }
        }
    }

    public function testItMapsSiteCategoryToTheConnectorClass(): void
    {
        $this->assertSame(SiteCategory::class, (new ClassMap())->get()['SiteCategory']);
    }

    /**
     * Every override must still be usable where the SDK class is type hinted or checked
     */
    public function testEveryOverrideExtendsTheSdkClassItReplaces(): void
    {
        $sdkMap = require dirname((new \ReflectionClass(\NetSuite\NetSuiteClient::class))->getFileName())
            . '/includes/classmap.php';

        foreach (ClassMap::OVERRIDES as $type => $class) {
            $this->assertTrue(is_a($class, $sdkMap[$type], true), $type);
        }
    }

    /**
     * SiteCategory also receives the item's web store category entries: it must declare every
     * field of that type, or hydrating an item creates dynamic properties again
     */
    public function testSiteCategoryDeclaresEveryFieldOfTheItemSiteCategory(): void
    {
        $itemFields = (new \ReflectionClass(SiteCategory_accounting::class))->getProperties(
            \ReflectionProperty::IS_PUBLIC
        );

        $this->assertNotEmpty($itemFields);
        foreach ($itemFields as $field) {
            if (!$field->isStatic()) {
                $this->assertTrue(property_exists(SiteCategory::class, $field->getName()), $field->getName());
            }
        }
    }

    /**
     * The failure that the override fixes: setting an item entry's field must not create a dynamic property
     */
    public function testAnItemSiteCategoryEntryCanBeHydratedWithoutDynamicProperties(): void
    {
        set_error_handler(static function (int $errno, string $message): bool {
            throw new \ErrorException($message, 0, $errno);
        });
        try {
            $entry = new SiteCategory();
            $entry->category = new \NetSuite\Classes\RecordRef();
            $entry->isDefault = true;
            $entry->categoryDescription = 'Lighting';
        } finally {
            restore_error_handler();
        }

        $this->assertTrue($entry->isDefault);
    }
}
