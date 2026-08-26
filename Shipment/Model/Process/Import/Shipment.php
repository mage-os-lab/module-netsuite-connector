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
 */

declare(strict_types=1);

namespace MageOS\NetSuiteConnector\Shipment\Model\Process\Import;

use MageOS\NetSuiteConnector\Core\Exception\SkipRecordException;
use MageOS\NetSuiteConnector\Core\Model\Process\Import\AbstractImportProcessor;
use NetSuite\Classes\ItemFulfillment;
use NetSuite\Classes\ItemFulfillmentShipStatus;
use NetSuite\Classes\Record;
use NetSuite\Classes\RecordType;

/**
 * This class creates new magento shipment based on NS shipment data, add tracking data and save. In case of magento
 * shipment exists in DB - clear its items and add them from NS shipment data.
 *
 *  next phpmd exclusions connected to inheritance usage for
 *  the processor and will gone away after removing it in the core
 * @SuppressWarnings(PHPMD).CouplingBetweenObjects
 * @SuppressWarnings(PHPMD).ExcessiveParameterList
 */
class Shipment extends AbstractImportProcessor
{
    public const MESSAGE_TYPE = "shipment";
    /**
     * @var \MageOS\NetSuiteConnector\Order\Api\OrderRegistryInterface
     */
    private $orderRegistry;

    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    /**
     * @var \MageOS\NetSuiteConnector\Shipment\Model\Mapper\ShipmentInterface
     */
    private $shipmentMapper;

    /**
     * @var \MageOS\NetSuiteConnector\Shipment\Model\ShipmentRegistry
     */
    private $shipmentRegistry;

    /**
     * @var \MageOS\NetSuiteConnector\Shipment\Model\Process\Import\Shipment\TrackingInfo
     */
    private $trackingInfo;

    /**
     * @var \MageOS\NetSuiteConnector\Shipment\Model\Process\Import\Shipment\TrackingInfoSender
     */
    private $trackingInfoSender;

    /**
     * @var int
     */
    private $recordLimit = 10;
    /**
     * @var Shipment\CleanUpManager
     */
    private $cleanUpManager;

    /**
     * Shipment constructor.
     * @param \MageOS\NetSuiteConnector\Shipment\Model\ConfigProvider\Permissions $permissionHelper
     * @param \Magento\Framework\Model\Context $context
     * @param \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement
     * @param \MageOS\NetSuiteConnector\Order\Api\OrderRegistryInterface $orderRegistry
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param \MageOS\NetSuiteConnector\Shipment\Model\Mapper\ShipmentInterface $shipmentMapper
     * @param \MageOS\NetSuiteConnector\Shipment\Model\ShipmentRegistry $shipmentRegistry
     * @param Shipment\TrackingInfo $trackingInfo
     * @param Shipment\TrackingInfoSender $trackingInfoSender
     * @param Shipment\CleanUpManager $cleanUpManager
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Shipment\Model\ConfigProvider\Permissions $permissionHelper,
        \Magento\Framework\Model\Context $context,
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement,
        \MageOS\NetSuiteConnector\Order\Api\OrderRegistryInterface $orderRegistry,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \MageOS\NetSuiteConnector\Shipment\Model\Mapper\ShipmentInterface $shipmentMapper,
        \MageOS\NetSuiteConnector\Shipment\Model\ShipmentRegistry $shipmentRegistry,
        \MageOS\NetSuiteConnector\Shipment\Model\Process\Import\Shipment\TrackingInfo $trackingInfo,
        \MageOS\NetSuiteConnector\Shipment\Model\Process\Import\Shipment\TrackingInfoSender $trackingInfoSender,
        \MageOS\NetSuiteConnector\Shipment\Model\Process\Import\Shipment\CleanUpManager $cleanUpManager
    ) {
        $this->orderRegistry = $orderRegistry;
        $this->shipmentRepository = $shipmentRepository;
        $this->shipmentMapper = $shipmentMapper;
        $this->shipmentRegistry = $shipmentRegistry;
        $this->trackingInfo = $trackingInfo;
        $this->trackingInfoSender = $trackingInfoSender;
        $this->cleanUpManager = $cleanUpManager;
        parent::__construct($permissionHelper, $context, $serviceManagement);
    }

    /**
     * @inheritdoc
     */
    public function getPermissionName(): string
    {
        return \MageOS\NetSuiteConnector\Shipment\Model\ConfigProvider\Permissions::GET_SHIPMENTS;
    }

    /**
     * @inheritdoc
     */
    public function isMagentoImportable(Record $itemShipment): bool
    {
        /** @var ItemFulfillment $itemShipment */
        if ($itemShipment->createdFrom === null) {
            return false;
        }

        //We don't import a shipment unless it achieved the "Shipped" status
        if ($itemShipment->shipStatus != ItemFulfillmentShipStatus::_shipped) {
            return false;
        }

        $netsuiteOrderId = $itemShipment->createdFrom->internalId;
        if ($this->orderRegistry->getOrderByNetSuiteId($netsuiteOrderId) === null) {
            return false;
        }

        return true;
    }

    /**
     * Check whether given shipment record is already imported
     *
     * @param Record $record
     * @return boolean
     */
    public function isAlreadyImported(Record $record): bool
    {
        $shipment = $this->shipmentRegistry->getShipmentByNetsuiteId($record->internalId);

        if (!$shipment) {
            return false;
        }

        $netsuiteUpdateDatetime = \MageOS\NetSuiteConnector\Core\Model\NetSuite\ConvertDate::fromNetSuiteToSql(
            $record->lastModifiedDate
        );
        if (strtotime($shipment->getData('netsuite_last_import_date')) > strtotime($netsuiteUpdateDatetime)) {
            return true;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function getRecordType(): string
    {
        return RecordType::itemFulfillment;
    }

    /**
     * @inheritdoc
     */
    public function getMessageType(): string
    {
        return self::MESSAGE_TYPE;
    }

    /**
     * @inheritdoc
     */
    public function isActive(): bool
    {
        return true;
    }

    /**
     * @param Record $itemShipment
     * @throws \Exception
     */
    public function process(Record $itemShipment)
    {
        $sentTrackingInformation = $this->trackingInfoSender->isSentTrackingInformation($itemShipment);
        $magentoShippings = $this->shipmentMapper->getMagentoFormat($itemShipment);
        // it is important to check tracking information before the cleanup, because existing tracking information
        // will be removed from existing shipment
        foreach ($magentoShippings as $shipping) {
            $this->cleanUpManager->prepare($itemShipment, $shipping);

            if ((!$shipping->getEntityId() || null !== $shipping->getItems())
                && !\count($shipping->getAllItems())
            ) {
                throw new SkipRecordException('Not saving empty shipment');
            }

            $this->shipmentRepository->save($shipping);

            $this->trackingInfo->addTrackingInformation($itemShipment, $shipping);
            if ($sentTrackingInformation) {
                $this->trackingInfoSender->sendTrackingInformation($shipping);
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function getRecordLimit(): int
    {
        return $this->recordLimit;
    }
}
