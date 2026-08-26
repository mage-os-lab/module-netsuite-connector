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

declare(strict_types=1);

namespace MageOS\NetSuiteConnector\Product\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Tax\Api\TaxClassManagementInterface;

/**
 * This class provides an array of all product tax classes as value-label options with "None" option
 */
class TaxClass implements OptionSourceInterface
{
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Tax\Api\TaxClassRepositoryInterface
     */
    private $taxClassRepository;

    /**
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassRepository
     */
    public function __construct(
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->taxClassRepository = $taxClassRepository;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];
        $options[] = ['value' => '0', 'label' => __('None')];
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('class_type', TaxClassManagementInterface::TYPE_PRODUCT)->create();
        $result = $this->taxClassRepository->getList($searchCriteria);
        foreach ($result->getItems() as $taxClass) {
            $options[] = [
                'value' => $taxClass->getClassId(),
                'label' => $taxClass->getClassName(),
            ];
        }
        return $options;
    }
}
