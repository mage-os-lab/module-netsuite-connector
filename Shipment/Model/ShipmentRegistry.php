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


namespace MageOS\NetSuiteConnector\Shipment\Model;

use Magento\Sales\Api\Data\ShipmentInterface;

/**
 * This class is responsible for loading shipment by NS internal ID. Loaded shipments are cached inside the class
 * variable.
 */
class ShipmentRegistry
{
    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var array
     */
    private $shipmentCache = [];

    /**
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->shipmentRepository = $shipmentRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Load magento shipment based on NS internal ID
     *
     * @param int $internalNetSuiteId
     * @return ShipmentInterface|null
     */
    public function getShipmentByNetsuiteId($internalNetSuiteId)
    {
        if (isset($this->shipmentCache[$internalNetSuiteId])) {
            return $this->shipmentCache[$internalNetSuiteId];
        }

        $this->searchCriteriaBuilder->addFilter('netsuite_internal_id', $internalNetSuiteId);
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $shipments = $this->shipmentRepository->getList($searchCriteria)->getItems();

        if (\count($shipments)) {
            $this->shipmentCache[$internalNetSuiteId] = array_pop($shipments);
            return $this->shipmentCache[$internalNetSuiteId];
        }

        return null;
    }
}
