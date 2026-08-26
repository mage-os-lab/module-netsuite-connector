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
namespace MageOS\NetSuiteConnector\Shipment\Model\Mapper\Shipment;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\Order\Item;
use NetSuite\Classes\ItemFulfillmentItem;
use NetSuite\Classes\Record;

/**
 * This class adds items to magento shipment using data from the netsuite fulfillment
 *
 * the warnings for the class suppressed due to ITEM_GROUP temporary solution
 * @SuppressWarnings(PHPMD).NPathComplexity
 * @SuppressWarnings(PHPMD).CyclomaticComplexity
 * @SuppressWarnings(PHPMD).CouplingBetweenObjects
 */
class Items
{
    private \Magento\Framework\DataObject\Copy $objectCopyService;
    private \Magento\Sales\Api\Data\ShipmentItemInterfaceFactory $shipmentItemFactory;
    private \MageOS\NetSuiteConnector\Product\Model\ResourceModel\Repository $netSuiteProductRepository;

    /**
     * Items constructor.
     * @param \Magento\Framework\DataObject\Copy $objectCopyService
     * @param \Magento\Sales\Api\Data\ShipmentItemInterfaceFactory $shipmentItemFactory
     * @param \MageOS\NetSuiteConnector\Product\Model\ResourceModel\Repository $netSuiteProductRepository
     */
    public function __construct(
        \Magento\Framework\DataObject\Copy $objectCopyService,
        \Magento\Sales\Api\Data\ShipmentItemInterfaceFactory $shipmentItemFactory,
        \MageOS\NetSuiteConnector\Product\Model\ResourceModel\Repository $netSuiteProductRepository
    ) {
        $this->objectCopyService = $objectCopyService;
        $this->shipmentItemFactory = $shipmentItemFactory;
        $this->netSuiteProductRepository = $netSuiteProductRepository;
    }

    /**
     * Add shipment items based on netsuiteShipment data from NS to magento Shipment
     *
     * @param Record $netsuiteShipment
     * @param ShipmentInterface $magentoShipment
     * @param OrderInterface $magentoOrder
     */
    public function addItems(
        Record $netsuiteShipment,
        ShipmentInterface $magentoShipment,
        OrderInterface $magentoOrder
    ): void {
        $shipmentMap = $this->prepareItemMap($netsuiteShipment, $magentoOrder);

        $totalQuantity = 0;
        foreach ($shipmentMap as $key => $shipmentMapItem) {
            $magentoShipmentItem = $this->shipmentItemFactory->create();

            $this->objectCopyService->copyFieldsetToTarget(
                'sales_convert_order_item',
                'to_shipment_item',
                $shipmentMapItem['magento_orderitem_object'],
                $magentoShipmentItem
            );

            $qty = ($shipmentMapItem['netsuite_object']->quantity <=
                $shipmentMapItem['magento_orderitem_object']->getQtyOrdered()) ?
                $shipmentMapItem['netsuite_object']->quantity :
                $shipmentMapItem['magento_orderitem_object']->getQtyOrdered();
            $magentoShipmentItem->setOrderItem($shipmentMapItem['magento_orderitem_object']);
            $magentoShipmentItem->setProductId($shipmentMapItem['magento_orderitem_object']->getProductId());
            $magentoShipmentItem->setData(
                'qty',
                $qty
            );

            $magentoShipment->addItem($magentoShipmentItem);
            if (!$shipmentMapItem['magento_orderitem_object']->getParentItemId()
                && !$shipmentMapItem['magento_orderitem_object']->isDeleted()
            ) {
                $totalQuantity += $qty;
            }
        }

        $magentoShipment->setTotalQty($totalQuantity);
    }

    /**
     * Prepare order items data for adding to shipment
     *
     * @param Record $netsuiteShipment
     * @param OrderInterface $magentoOrder
     * @return array
     */
    private function prepareItemMap(Record $netsuiteShipment, OrderInterface $magentoOrder): array
    {
        $compositeItems = $this->collectCompositeItems($netsuiteShipment, $magentoOrder);

        foreach ($netsuiteShipment->itemList->item as $netsuiteShipmentItem) {
            $itemGroupNetsuiteId = \MageOS\NetSuiteConnector\Core\Model\NetSuite\CustomFieldAccess::get(
                $netsuiteShipmentItem,
                \MageOS\NetSuiteConnector\Shipment\Model\Mapper\ShipmentInterface::ITEM_GROUP_ITEM_FIELD
            );

            if (null !== $itemGroupNetsuiteId) {
                if (!array_key_exists($itemGroupNetsuiteId, $compositeItems)) {
                    continue;
                }
                $shipmentMap[] = $this->prepareSimpleItemMap(
                    $netsuiteShipmentItem,
                    $compositeItems[$itemGroupNetsuiteId]
                );
                unset($compositeItems[$itemGroupNetsuiteId]);
                continue;
            }

            foreach ($magentoOrder->getAllItems() as $magentoOrderItem) {
                if (!$this->netSuiteProductRepository->isEqual($netsuiteShipmentItem->item, $magentoOrderItem)) {
                    continue;
                }
                if ($magentoOrderItem->getParentItemId() && $magentoOrderItem->getParentItem()) {
                    $shipmentMap[] = $this->prepareCompositeItemMap(
                        $netsuiteShipmentItem,
                        $magentoOrderItem,
                        $magentoOrder
                    );
                } else {
                    $shipmentMap[] = $this->prepareSimpleItemMap($netsuiteShipmentItem, $magentoOrderItem);
                }
            }
        }
        return $shipmentMap;
    }

    private function collectCompositeItems(Record $netsuiteShipment, OrderInterface $magentoOrder) : array
    {
        $compositeItems = [];
        foreach ($netsuiteShipment->itemList->item as $netsuiteShipmentItem) {
            $itemGroupNetsuiteId = \MageOS\NetSuiteConnector\Core\Model\NetSuite\CustomFieldAccess::get(
                $netsuiteShipmentItem,
                \MageOS\NetSuiteConnector\Shipment\Model\Mapper\ShipmentInterface::ITEM_GROUP_ITEM_FIELD
            );

            if (null === $itemGroupNetsuiteId) {
                continue;
            }

            foreach ($magentoOrder->getAllItems() as $magentoOrderItem) {
                $product = $magentoOrderItem->getProduct();
                if ($product?->getId() === null) {
                    continue;
                }

                if ($product->getNetsuiteInternalId() != $itemGroupNetsuiteId) {
                    continue;
                }

                if (!array_key_exists($itemGroupNetsuiteId, $compositeItems)) {
                    $compositeItems[$itemGroupNetsuiteId] = $magentoOrderItem;
                }
            }
        }
        return $compositeItems;
    }

    /**
     * Prepare mapping for given simple item
     *
     * @param ItemFulfillmentItem $netsuiteShipmentItem
     * @param Item $magentoOrderItem
     * @return array
     */
    private function prepareSimpleItemMap(
        ItemFulfillmentItem $netsuiteShipmentItem,
        Item $magentoOrderItem
    ): array {
        $shipmentMapItem = [];
        $shipmentMapItem['netsuite_object'] = $netsuiteShipmentItem;
        $shipmentMapItem['magento_orderitem_object'] = $magentoOrderItem;
        return $shipmentMapItem;
    }

    /**
     * Prepare mapping for given composite item
     *
     * Only configurable is supported
     *
     * @param ItemFulfillmentItem $netsuiteShipmentItem
     * @param Item $magentoOrderItem
     * @return array
     */
    private function prepareCompositeItemMap(
        ItemFulfillmentItem $netsuiteShipmentItem,
        Item $magentoOrderItem,
        OrderInterface $magentoOrder
    ): ?array {
        if ($magentoOrderItem->getParentItem()->getProductType() !== Configurable::TYPE_CODE) {
            return null;
        }
        $shipmentMapItem = [];
        $shipmentMapItem['netsuite_object'] = $netsuiteShipmentItem;

        $magentoOrderItems = $magentoOrder->getAllItems();
        reset($magentoOrderItems);

        foreach ($magentoOrderItems as $magentoOrderItem2) {
            if ($magentoOrderItem2->getId() == $magentoOrderItem->getParentItemId()) {
                $newOrderItem = clone $magentoOrderItem2;
                $newOrderItem->setWeight($magentoOrderItem->getWeight());

                $shipmentMapItem['magento_orderitem_object'] = $newOrderItem;
                break;
            }
        }

        return $shipmentMapItem;
    }
}
