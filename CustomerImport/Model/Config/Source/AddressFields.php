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
 * Class AddressFields - used for system config
 */
class AddressFields implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Get options in "key-value" format
     *
     * @return array
     * @throws LocalizedException
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'phone', 'label' => 'Telephone'],
            ['value' => 'addr1', 'label' => 'Street'],
            ['value' => 'zip', 'label' => 'Postalcode'],
            ['value' => 'city', 'label' => 'City'],
            ['value' => 'state', 'label' => 'Region/State'],
            ['value' => 'country', 'label' => 'Country']
        ];
    }
}
