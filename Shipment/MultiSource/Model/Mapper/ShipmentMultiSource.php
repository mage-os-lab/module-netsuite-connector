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

namespace MageOS\NetSuiteConnector\Shipment\MultiSource\Model\Mapper;

use NetSuite\Classes\Record;
use MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException;
use MageOS\NetSuiteConnector\Core\Exception\SkipRecordException;

/**
 * This class converts NS fulfillment into magento shipment/'s for Multi Stock/Source Inventory Management
 */
class ShipmentMultiSource implements \MageOS\NetSuiteConnector\Shipment\Model\Mapper\ShipmentInterface
{
    private \Magento\Sales\Api\Data\ShipmentInterfaceFactory $shipmentFactory;
    private \MageOS\NetSuiteConnector\Order\Api\OrderRegistryInterface $orderRegistry;
    private \MageOS\NetSuiteConnector\Customer\Model\Mapper\Customer $customerMapper;
    private \Magento\Framework\DataObject\Copy $objectCopyService;
    private \MageOS\NetSuiteConnector\Shipment\Model\Mapper\Shipment\Items $itemsMapper;
    private \MageOS\NetSuiteConnector\Shipment\Model\Mapper\Shipment\Address $addressMapper;
    private \MageOS\NetSuiteConnector\Shipment\MultiSource\Model\Mapper\ShipmentMultiSource\LocationGrouper $locationGrouper;

    /**
     * Shipment constructor.
     * @param \Magento\Sales\Api\Data\ShipmentInterfaceFactory $shipmentFactory
     * @param \MageOS\NetSuiteConnector\Order\Api\OrderRegistryInterface $orderRegistry
     * @param \MageOS\NetSuiteConnector\Customer\Model\Mapper\Customer $customerMapper
     * @param \Magento\Framework\DataObject\Copy $objectCopyService
     * @param \MageOS\NetSuiteConnector\Shipment\Model\Mapper\Shipment\Items $itemsMapper
     * @param \MageOS\NetSuiteConnector\Shipment\Model\Mapper\Shipment\Address $addressMapper
     * @param \MageOS\NetSuiteConnector\Shipment\MultiSource\Model\Mapper\ShipmentMultiSource\LocationGrouper $locationGrouper
     */
    public function __construct(
        \Magento\Sales\Api\Data\ShipmentInterfaceFactory $shipmentFactory,
        \MageOS\NetSuiteConnector\Order\Api\OrderRegistryInterface $orderRegistry,
        \MageOS\NetSuiteConnector\Customer\Model\Mapper\Customer $customerMapper,
        \Magento\Framework\DataObject\Copy $objectCopyService,
        \MageOS\NetSuiteConnector\Shipment\Model\Mapper\Shipment\Items $itemsMapper,
        \MageOS\NetSuiteConnector\Shipment\Model\Mapper\Shipment\Address $addressMapper,
        \MageOS\NetSuiteConnector\Shipment\MultiSource\Model\Mapper\ShipmentMultiSource\LocationGrouper $locationGrouper
    ) {
        $this->shipmentFactory = $shipmentFactory;
        $this->orderRegistry = $orderRegistry;
        $this->customerMapper = $customerMapper;
        $this->objectCopyService = $objectCopyService;
        $this->itemsMapper = $itemsMapper;
        $this->addressMapper = $addressMapper;
        $this->locationGrouper = $locationGrouper;
    }

    /**
     * @inheritDoc
     * @throws SkipRecordException
     * @throws DataIntegrityException
     */
    public function getMagentoFormat(Record $baseNetsuiteShipment): array
    {
        $shipments = [];

        if (!$baseNetsuiteShipment->createdFrom) {
            throw new SkipRecordException("Shipment has empty createdFrom");
        }

        if (!$baseNetsuiteShipment->entity) {
            throw new SkipRecordException("Shipment has empty entity");
        }

        $netsuiteOrderId = $baseNetsuiteShipment->createdFrom->internalId;
        $magentoOrder = $this->orderRegistry->getOrderByNetSuiteId($netsuiteOrderId);
        if ($magentoOrder === null) {
            throw new DataIntegrityException(
                "Order with netsuite_internal_id {$baseNetsuiteShipment->createdFrom->internalId} not found in Magento"
            );
        }
        $netsuiteCustomer = $this->customerMapper->getByInternalId($baseNetsuiteShipment->entity->internalId);

        /**
         * we group the items by locations cloning netsuite fulfillments and process each as separate shipment
         */
        foreach ($this->locationGrouper->group($baseNetsuiteShipment) as $netsuiteShipment) {
            $magentoShipment = $this->shipmentFactory->create();
            $magentoShipment->setStoreId($magentoOrder->getStoreId());
            //we assume customer is not changed for shipping
            $magentoShipment->setCustomerId($magentoOrder->getCustomerId());
            //billing address is not part of a netsuite fulfillment, use the one in Magento
            $magentoShipment->setBillingAddressId($magentoOrder->getBillingAddressId());
            $magentoShippingAddress = $this->addressMapper->addShippingAddress(
                $netsuiteShipment,
                $netsuiteCustomer,
                $magentoOrder
            );

            $this->objectCopyService->copyFieldsetToTarget(
                'sales_convert_order',
                'to_shipment',
                $magentoOrder,
                $magentoShipment
            );
            $magentoShipment->setShippingAddressId($magentoShippingAddress->getEntityId());
            $magentoShipment->setOrderId($magentoOrder->getEntityId());
            $this->itemsMapper->addItems($netsuiteShipment, $magentoShipment, $magentoOrder);
            $shipments[] = $magentoShipment;
        }
        return $shipments;
    }
}
