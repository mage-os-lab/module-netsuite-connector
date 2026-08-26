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

namespace MageOS\NetSuiteConnector\Product\Model;

/**
 * This class provides a list of all product attribute codes as value-label options
 */
class ProductAttributeCodeList
{
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Catalog\Api\ProductAttributeRepositoryInterface
     */
    private $productAttributeRepository;

    /**
     * @var array
     */
    private $productAttributesCodes;

    /**
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Catalog\Api\ProductAttributeRepositoryInterface $productAttributeRepository
     */
    public function __construct(
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Catalog\Api\ProductAttributeRepositoryInterface $productAttributeRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productAttributeRepository = $productAttributeRepository;
    }

    /**
     * Get a list of all available product attribute codes as value-label options + possible empty option
     *
     * @param bool $withEmpty
     * @return array
     */
    public function getOptionList($withEmpty = false): array
    {
        $options = $this->getProductAttributes();
        if ($withEmpty) {
            array_unshift($options, ['value' => '', 'label' => __('None')]);
        }
        return $options;
    }

    /**
     * Get a list of all available product attribute codes as value-label options
     *
     * @return array
     */
    private function getProductAttributes(): array
    {
        if (is_array($this->productAttributesCodes)) {
            return $this->productAttributesCodes;
        }
        $this->productAttributesCodes = [];
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $result = $this->productAttributeRepository->getList($searchCriteria);

        foreach ($result->getItems() as $attribute) {
            $this->productAttributesCodes[] = [
                'value' => $attribute->getAttributeCode(),
                //phpcs:ignore
                'label' => addslashes($attribute->getDefaultFrontendLabel() . ' (' . $attribute->getAttributeCode() . ')')
            ];
        }
        asort($this->productAttributesCodes);
        return $this->productAttributesCodes;
    }
}
