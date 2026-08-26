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

namespace MageOS\NetSuiteConnector\Customer\Model\Config\Source;

use Magento\Tax\Model\ClassModel;
use Magento\Tax\Model\ResourceModel\TaxClass\CollectionFactory;

/**
 * Class TaxCustomer
 */
class TaxCustomer implements \Magento\Framework\Data\OptionSourceInterface
{

    /** @var CollectionFactory */
    protected $taxClassFactory;

    /**
     * TaxCustomer constructor.
     * @param CollectionFactory $taxClassFactory
     */
    public function __construct(CollectionFactory $taxClassFactory)
    {
        $this->taxClassFactory = $taxClassFactory;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = $this->toArray();
        $optionArray = [];
        foreach ($options as $key => $value) {
            $optionArray[] = ['label' => $value, 'value' => $key];
        }
        return $optionArray;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $options = [];

        $taxClassCollection = $this->taxClassFactory->create();
        $taxClassCollection->addFieldToFilter('class_type', ClassModel::TAX_CLASS_TYPE_CUSTOMER);

        foreach ($taxClassCollection as $taxClassCollectionItem) {
            $options[$taxClassCollectionItem->getId()] = $taxClassCollectionItem->getClassName();
        }

        return $options;
    }
}
