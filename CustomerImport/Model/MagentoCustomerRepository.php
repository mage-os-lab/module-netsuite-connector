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

namespace MageOS\NetSuiteConnector\CustomerImport\Model;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class MagentoCustomerRepository - responsible for extracting and saving customers entities for module use
 */
class MagentoCustomerRepository
{
    private array $customerCache = [];
    private \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder;
    private \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository;

    /**
     * MagentoCustomerRepository constructor.
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param $byField
     * @param $searchValue
     * @return \Magento\Customer\Api\Data\CustomerInterface|mixed|null
     * @throws LocalizedException
     */
    public function getCustomerByField(string $byField, $searchValue): ?CustomerInterface
    {
        if (isset($this->customerCache[$searchValue])) {
            return $this->customerCache[$searchValue];
        }

        $this->searchCriteriaBuilder->addFilter($byField, $searchValue);
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $customers = $this->customerRepository->getList($searchCriteria)->getItems();

        if (count($customers) == 0) {
            return null;
        }

        $this->customerCache[$searchValue] = array_pop($customers);
        return $this->customerCache[$searchValue];
    }
}
