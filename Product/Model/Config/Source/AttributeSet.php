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

/**
 * This class provides an array of all product attribute sets as value-label options
 */
class AttributeSet implements OptionSourceInterface
{
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Catalog\Api\AttributeSetRepositoryInterface
     */
    private $attributeSetRepository;

    /**
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Catalog\Api\AttributeSetRepositoryInterface $attributeSetRepository
     */
    public function __construct(
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Catalog\Api\AttributeSetRepositoryInterface $attributeSetRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->attributeSetRepository = $attributeSetRepository;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $result = $this->attributeSetRepository->getList($searchCriteria);
        foreach ($result->getItems() as $attributeSet) {
            $options[] = [
                'value' => $attributeSet->getAttributeSetId(),
                'label' => $attributeSet->getAttributeSetName()
            ];
        }
        return $options;
    }
}
