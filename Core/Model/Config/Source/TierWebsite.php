<?php

namespace MageOS\NetSuiteConnector\Core\Model\Config\Source;

class TierWebsite extends Visibility
{

    /** @var \Magento\Store\Model\ResourceModel\Website\CollectionFactory  */
    protected $websiteFactory;

    public function __construct(\Magento\Store\Model\ResourceModel\Website\CollectionFactory $websiteFactory)
    {
        $this->websiteFactory = $websiteFactory;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $options = [];
        $options[0] = __('All websites');

        $websiteCollection = $this->websiteFactory->create();
        foreach ($websiteCollection as $websiteCollectionItem) {
            $options[$websiteCollectionItem->getWebsiteId()]=$websiteCollectionItem->getName();
        }

        return $options;
    }
}
