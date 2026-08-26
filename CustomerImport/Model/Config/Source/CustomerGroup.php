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

namespace MageOS\NetSuiteConnector\CustomerImport\Model\Config\Source;

use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class CustomerGroup - used for system config
 */
class CustomerGroup implements \Magento\Framework\Data\OptionSourceInterface
{
    private const NOT_LOGGED_ID = 0;

    /**
     * @var GroupRepositoryInterface
     */
    private $groupRepository;
    /**
     * @var SearchCriteriaInterface
     */
    private $searchCriteriaBuilderFactory;

    /**
     * CustomerGroup constructor.
     * @param GroupRepositoryInterface $groupRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     */
    public function __construct(
        GroupRepositoryInterface $groupRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
    ) {
        $this->groupRepository = $groupRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     * @throws LocalizedException
     */
    public function toOptionArray(): array
    {
        $options = [];
        $searchCriteria = $this->searchCriteriaBuilderFactory
            ->create()
            ->addFilter('customer_group_id', self::NOT_LOGGED_ID, 'neq')
            ->create();
        $groupList = $this->groupRepository->getList($searchCriteria)->getItems();
        foreach ($groupList as $group) {
            $options[$group->getId()] = $group->getCode();
        }
        return $options;
    }
}
