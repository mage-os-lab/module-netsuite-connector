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

namespace MageOS\NetSuiteConnector\Customer\Api\CustomerGroups;

use MageOS\NetSuiteConnector\Customer\Model\CustomerGroups\ExtraAttributesInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * Interface ExtraAttributesRepositoryInterface
 */
interface ExtraAttributesRepositoryInterface
{
    /**
     * @param ExtraAttributesInterface $page
     * @return mixed
     */
    public function save(ExtraAttributesInterface $page);

    /**
     * @param $id
     * @return mixed
     */
    public function getById($id);

    /**
     * @param SearchCriteriaInterface $criteria
     * @return mixed
     */
    public function getList(SearchCriteriaInterface $criteria);

    /**
     * @param ExtraAttributesInterface $page
     * @return mixed
     */
    public function delete(ExtraAttributesInterface $page);

    /**
     * @param $id
     * @return mixed
     */
    public function deleteById($id);
}
