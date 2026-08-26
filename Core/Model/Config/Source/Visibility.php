<?php

namespace MageOS\NetSuiteConnector\Core\Model\Config\Source;

class Visibility implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = $this->toArray();
        $ret = [];
        foreach ($options as $key => $value) {
            $ret[]=['label'=>$value,'value'=>$key];
        }
        return $ret;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE => __('Not Visible'),
            \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_CATALOG => __('Catalog'),
            \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH => __('Search'),
            \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH => __('Catalog, Search'),
        ];
    }
}
