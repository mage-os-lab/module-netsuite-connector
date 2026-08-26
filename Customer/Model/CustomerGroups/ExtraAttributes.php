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

namespace MageOS\NetSuiteConnector\Customer\Model\CustomerGroups;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * Class ExtraAttributes
 */
class ExtraAttributes extends AbstractModel implements ExtraAttributesInterface, IdentityInterface
{
    public const CACHE_TAG = 'rocketweb_ns_customergroups';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(\MageOS\NetSuiteConnector\Customer\Model\CustomerGroups\ResourceModel\ExtraAttributes::class);
    }

    /**
     * @return array|string[]
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @param $groupId int
     * @return ExtraAttributesInterface
     */
    public function setCustomerGroupId($groupId)
    {
        $this->setData('customer_group_id', $groupId);
        return $this;
    }

    /**
     * @return int
     */
    public function getCustomerGroupId()
    {
        return $this->_getData('customer_group_id');
    }

    /**
     * @param $internalId string
     * @return ExtraAttributesInterface
     */
    public function setNetsuiteInternalId($internalId)
    {
        $this->setData('netsuite_internal_id', $internalId);
        return $this;
    }

    /**
     * Get netsuite internal id
     * @return string
     */
    public function getNetsuiteInternalId()
    {
        return $this->_getData('netsuite_internal_id');
    }
}
