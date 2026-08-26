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

namespace MageOS\NetSuiteConnector\CustomerImport\Test\Unit\Model\Config\Source;

use MageOS\NetSuiteConnector\CustomerImport\Model\Config\Source\Store;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use PHPUnit\Framework\TestCase;

class StoreTest extends TestCase
{
    /**
     * The source must satisfy the interface the system configuration expects.
     */
    public function testItIsAnOptionSource(): void
    {
        $this->assertInstanceOf(OptionSourceInterface::class, $this->createSource([]));
    }

    /**
     * Every store view must be offered keyed by its own store id.
     */
    public function testItOffersEveryStoreKeyedByItsId(): void
    {
        $source = $this->createSource([
            'default' => $this->createStore(1, 'Default Store View'),
            'wholesale' => $this->createStore(2, 'Wholesale Store View'),
        ]);

        $this->assertSame(
            [
                1 => 'Default Store View',
                2 => 'Wholesale Store View',
            ],
            $source->toArray()
        );
    }

    /**
     * The admin store must never be offered as an import target.
     */
    public function testItExcludesTheAdminStore(): void
    {
        $source = $this->createSource([
            'admin' => $this->createStore(0, 'Admin'),
            'default' => $this->createStore(1, 'Default Store View'),
        ]);

        $this->assertSame([1 => 'Default Store View'], $source->toArray());
    }

    /**
     * The option array must pair each store label with its store id.
     */
    public function testItConvertsTheStoresIntoLabelAndValuePairs(): void
    {
        $source = $this->createSource([
            'admin' => $this->createStore(0, 'Admin'),
            'default' => $this->createStore(1, 'Default Store View'),
            'wholesale' => $this->createStore(2, 'Wholesale Store View'),
        ]);

        $this->assertSame(
            [
                ['label' => 'Default Store View', 'value' => 1],
                ['label' => 'Wholesale Store View', 'value' => 2],
            ],
            $source->toOptionArray()
        );
    }

    /**
     * An installation holding only the admin store produces an empty option list.
     */
    public function testItReturnsAnEmptyListWhenOnlyTheAdminStoreExists(): void
    {
        $source = $this->createSource(['admin' => $this->createStore(0, 'Admin')]);

        $this->assertSame([], $source->toOptionArray());
    }

    /**
     * Build a store double with the given id and name.
     *
     * @param int $id
     * @param string $name
     * @return StoreInterface
     */
    private function createStore(int $id, string $name): StoreInterface
    {
        $store = $this->createStub(StoreInterface::class);
        $store->method('getId')->willReturn($id);
        $store->method('getName')->willReturn($name);

        return $store;
    }

    /**
     * Build the subject around a repository returning the given stores keyed by store code.
     *
     * @param array<string, StoreInterface> $stores
     * @return Store
     */
    private function createSource(array $stores): Store
    {
        $storeRepository = $this->createStub(StoreRepositoryInterface::class);
        $storeRepository->method('getList')->willReturn($stores);

        return new Store($storeRepository);
    }
}
