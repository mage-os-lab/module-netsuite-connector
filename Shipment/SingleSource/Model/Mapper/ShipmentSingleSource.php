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

namespace MageOS\NetSuiteConnector\Shipment\SingleSource\Model\Mapper;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\ShipmentInterface;
use NetSuite\Classes\Record;
use MageOS\NetSuiteConnector\Core\Exception\SkipRecordException;

/**
 * This class converts NS fulfillment into magento shipment for Single Stock/Source Inventory Management
 */
class ShipmentSingleSource implements \MageOS\NetSuiteConnector\Shipment\Model\Mapper\ShipmentInterface
{
    private \Magento\Sales\Api\Data\ShipmentInterfaceFactory $shipmentFactory;
    private \MageOS\NetSuiteConnector\Order\Api\OrderRegistryInterface $orderRegistry;
    private \MageOS\NetSuiteConnector\Customer\Model\Mapper\Customer $customerMapper;
    private \Magento\Framework\DataObject\Copy $objectCopyService;
    private \MageOS\NetSuiteConnector\Shipment\Model\Mapper\Shipment\Items $itemsMapper;
    private \MageOS\NetSuiteConnector\Shipment\Model\Mapper\Shipment\Address $addressMapper;

    /**
     * Shipment constructor.
     * @param \Magento\Sales\Api\Data\ShipmentInterfaceFactory $shipmentFactory
     * @param \MageOS\NetSuiteConnector\Order\Api\OrderRegistryInterface $orderRegistry
     * @param \MageOS\NetSuiteConnector\Customer\Model\Mapper\Customer $customerMapper
     * @param \Magento\Framework\DataObject\Copy $objectCopyService
     * @param \MageOS\NetSuiteConnector\Shipment\Model\Mapper\Shipment\Items $itemsMapper
     * @param \MageOS\NetSuiteConnector\Shipment\Model\Mapper\Shipment\Address $addressMapper
     */
    public function __construct(
        \Magento\Sales\Api\Data\ShipmentInterfaceFactory $shipmentFactory,
        \MageOS\NetSuiteConnector\Order\Api\OrderRegistryInterface $orderRegistry,
        \MageOS\NetSuiteConnector\Customer\Model\Mapper\Customer $customerMapper,
        \Magento\Framework\DataObject\Copy $objectCopyService,
        \MageOS\NetSuiteConnector\Shipment\Model\Mapper\Shipment\Items $itemsMapper,
        \MageOS\NetSuiteConnector\Shipment\Model\Mapper\Shipment\Address $addressMapper
    ) {
        $this->shipmentFactory = $shipmentFactory;
        $this->orderRegistry = $orderRegistry;
        $this->customerMapper = $customerMapper;
        $this->objectCopyService = $objectCopyService;
        $this->itemsMapper = $itemsMapper;
        $this->addressMapper = $addressMapper;
    }

    /**
     * @inheritDoc
     */
    public function getMagentoFormat(Record $netsuiteShipment): array
    {
        /** @var ShipmentInterface $netsuiteShipment */
        $magentoShipment = $this->shipmentFactory->create();

        if (!$netsuiteShipment->createdFrom) {
            throw new SkipRecordException("Shipment has empty createdFrom");
        }

        if (!$netsuiteShipment->entity) {
            throw new SkipRecordException("Shipment has empty entity");
        }

        $netsuiteOrderId = $netsuiteShipment->createdFrom->internalId;
        $magentoOrder = $this->orderRegistry->getOrderByNetSuiteId($netsuiteOrderId);
        if ($magentoOrder === null) {
            throw new NoSuchEntityException(
                __("Order with netsuite_internal_id {$netsuiteShipment->createdFrom->internalId} not found in Magento!")
            );
        }

        $netsuiteCustomer = $this->customerMapper->getByInternalId($netsuiteShipment->entity->internalId);

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
        $magentoShipment->setOrderId($magentoOrder->getId());
        $this->itemsMapper->addItems($netsuiteShipment, $magentoShipment, $magentoOrder);

        return [$magentoShipment];
    }
}
