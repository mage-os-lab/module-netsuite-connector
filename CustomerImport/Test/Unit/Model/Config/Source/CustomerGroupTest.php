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

use MageOS\NetSuiteConnector\CustomerImport\Model\Config\Source\CustomerGroup;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\Data\GroupSearchResultsInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Data\OptionSourceInterface;
use PHPUnit\Framework\TestCase;

class CustomerGroupTest extends TestCase
{
    /**
     * The source must satisfy the interface the system configuration expects.
     */
    public function testItIsAnOptionSource(): void
    {
        $this->assertInstanceOf(OptionSourceInterface::class, $this->createSource([]));
    }

    /**
     * Every customer group must be offered keyed by its own group id.
     */
    public function testItOffersEveryCustomerGroupKeyedByItsId(): void
    {
        $source = $this->createSource([
            $this->createGroup(1, 'General'),
            $this->createGroup(2, 'Wholesale'),
            $this->createGroup(3, 'Retailer'),
        ]);

        $this->assertSame(
            [
                1 => 'General',
                2 => 'Wholesale',
                3 => 'Retailer',
            ],
            $source->toOptionArray()
        );
    }

    /**
     * An empty group list produces an empty option list rather than an error.
     */
    public function testItReturnsAnEmptyListWhenNoGroupsExist(): void
    {
        $this->assertSame([], $this->createSource([])->toOptionArray());
    }

    /**
     * The not logged in group must be excluded through a not equal filter on the group id.
     */
    public function testItExcludesTheNotLoggedInGroupThroughTheSearchCriteria(): void
    {
        $searchCriteria = $this->createStub(SearchCriteriaInterface::class);

        $builder = $this->createMock(SearchCriteriaBuilder::class);
        $builder->expects($this->once())
            ->method('addFilter')
            ->with('customer_group_id', 0, 'neq')
            ->willReturnSelf();
        $builder->method('create')->willReturn($searchCriteria);

        $builderFactory = $this->createStub(SearchCriteriaBuilderFactory::class);
        $builderFactory->method('create')->willReturn($builder);

        $searchResults = $this->createStub(GroupSearchResultsInterface::class);
        $searchResults->method('getItems')->willReturn([]);

        $groupRepository = $this->createMock(GroupRepositoryInterface::class);
        $groupRepository->expects($this->once())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($searchResults);

        $source = new CustomerGroup($groupRepository, $builderFactory);

        $this->assertSame([], $source->toOptionArray());
    }

    /**
     * Build a customer group double with the given id and code.
     *
     * @param int $id
     * @param string $code
     * @return GroupInterface
     */
    private function createGroup(int $id, string $code): GroupInterface
    {
        $group = $this->createStub(GroupInterface::class);
        $group->method('getId')->willReturn($id);
        $group->method('getCode')->willReturn($code);

        return $group;
    }

    /**
     * Build the subject around a repository returning the given groups.
     *
     * @param array<int, GroupInterface> $groups
     * @return CustomerGroup
     */
    private function createSource(array $groups): CustomerGroup
    {
        $builder = $this->createStub(SearchCriteriaBuilder::class);
        $builder->method('addFilter')->willReturnSelf();
        $builder->method('create')->willReturn($this->createStub(SearchCriteriaInterface::class));

        $builderFactory = $this->createStub(SearchCriteriaBuilderFactory::class);
        $builderFactory->method('create')->willReturn($builder);

        $searchResults = $this->createStub(GroupSearchResultsInterface::class);
        $searchResults->method('getItems')->willReturn($groups);

        $groupRepository = $this->createStub(GroupRepositoryInterface::class);
        $groupRepository->method('getList')->willReturn($searchResults);

        return new CustomerGroup($groupRepository, $builderFactory);
    }
}
