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

use Magento\Store\Api\StoreRepositoryInterface;

/**
 * Class Store - used for system config;
 */
class Store implements \Magento\Framework\Data\OptionSourceInterface
{
    private const EXCLUDED_STORE_CODE = 'admin';
    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * Store constructor.
     * @param StoreRepositoryInterface $storeRepository
     */
    public function __construct(StoreRepositoryInterface $storeRepository)
    {
        $this->storeRepository = $storeRepository;
    }

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
        $options = [];

        $stores = $this->storeRepository->getList();
        unset($stores[self::EXCLUDED_STORE_CODE]);
        foreach ($stores as $store) {
            $options[$store->getId()] = $store->getName();
        }
        return $options;
    }
}
