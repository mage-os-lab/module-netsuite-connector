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

namespace MageOS\NetSuiteConnector\Order\Model\Process\Import;

use MageOS\NetSuiteConnector\Core\Model\Process\Import\AbstractImportProcessor;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use NetSuite\Classes\Record;
use NetSuite\Classes\RecordType;

/**
 * This class processes a saleOrder record object imported from NS and update corresponding order in magento DB: this
 * includes state/status and quantities.
 */
class Order extends AbstractImportProcessor
{
    public const MESSAGE_TYPE = 'order';
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \MageOS\NetSuiteConnector\Order\Model\Transform
     */
    private $transform;

    /**
     * @var \Magento\Sales\Api\Data\OrderExtensionFactory
     */
    private $orderExtensionFactory;

    /**
     * @var \MageOS\NetSuiteConnector\Order\Api\OrderRegistryInterface
     */
    private $orderRegistry;

    /**
     * @var int
     */
    private $recordLimit;

    protected bool $extraLoadRecordOnImport = false;

    /**
     *
     * @param \MageOS\NetSuiteConnector\Order\Model\ConfigProvider\Permissions $permissionHelper
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \MageOS\NetSuiteConnector\Order\Model\Transform $transform
     * @param \Magento\Sales\Api\Data\OrderExtensionFactory $orderExtensionFactory
     * @param \MageOS\NetSuiteConnector\Order\Api\OrderRegistryInterface $orderRegistry
     * @param int $recordLimit
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Order\Model\ConfigProvider\Permissions $permissionHelper,
        \Magento\Framework\Model\Context $context,
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\ServiceManagement $serviceManagement,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \MageOS\NetSuiteConnector\Order\Model\Transform $transform,
        \Magento\Sales\Api\Data\OrderExtensionFactory $orderExtensionFactory,
        \MageOS\NetSuiteConnector\Order\Api\OrderRegistryInterface $orderRegistry,
        $recordLimit = 10
    ) {
        $this->orderRepository = $orderRepository;
        $this->transform = $transform;
        $this->orderExtensionFactory = $orderExtensionFactory;
        $this->orderRegistry = $orderRegistry;
        $this->recordLimit = $recordLimit;
        parent::__construct($permissionHelper, $context, $serviceManagement);
    }

    /**
     * @inheritdoc
     */
    public function getPermissionName()
    {
        return \MageOS\NetSuiteConnector\Order\Model\ConfigProvider\Permissions::GET_ORDER_CHANGES;
    }

    /**
     * @inheritdoc
     */
    public function isMagentoImportable(Record $salesOrder)
    {
        /** @var \SalesOrder $salesOrder */

        // Exclude the orders that have the same creation and last modified date as:
        //  - they are already present in Magento in the same format (Magento sent them to NetSuite)
        //  - they are not part of Magento
        if ($salesOrder->lastModifiedDate == $salesOrder->createdDate) {
            return false;
        }

        if (!$salesOrder->itemList || !$salesOrder->itemList->item) {
            return false;
        }

        // check if the order already exists in Magento. If not, we do not care about
        // its updates as the order is not related to the store.
        if ($this->orderRegistry->getOrderByNetSuiteId($salesOrder->internalId)) {
            return true;
        }

        return false;
    }

    /**
     * Check whether given order record is already imported
     *
     * @param Record $record
     * @return boolean
     */
    public function isAlreadyImported(Record $record)
    {
        $order = $this->orderRegistry->getOrderByNetSuiteId($record->internalId);
        if (!$order) {
            return false;
        }
        $netsuiteUpdateDatetime = \MageOS\NetSuiteConnector\Core\Model\NetSuite\ConvertDate::fromNetSuiteToSql(
            $record->lastModifiedDate
        );
        if (strtotime($order->getData("netsuite_last_import_date")) > strtotime($netsuiteUpdateDatetime)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function getRecordType()
    {
        return RecordType::salesOrder;
    }

    /**
     * @inheritdoc
     */
    public function getMessageType()
    {
        return self::MESSAGE_TYPE;
    }

    /**
     * @inheritdoc
     */
    public function isActive()
    {
        return true;
    }

    /**
     * Update magento order with NS data from given $salesOrder
     *
     * @param Record $salesOrder
     */
    public function process(Record $salesOrder)
    {
        $magentoOrder = $this->orderRegistry->getOrderByNetSuiteId($salesOrder->internalId);

        $magentoOrderState = $this->transform->netsuiteStatusToMagentoOrderState($salesOrder->status);
        $magentoOrder->setState($magentoOrderState);
        $magentoOrder->setStatus($magentoOrderState);

        $extension = $magentoOrder->getExtensionAttributes();
        if ($extension === null) {
            $magentoOrder->setExtensionAttributes($this->orderExtensionFactory->create());
        }

        $magentoOrder->getExtensionAttributes()->setNetsuiteLastImportDate(
            \MageOS\NetSuiteConnector\Core\Model\NetSuite\ConvertDate::fromNetSuiteToSql(
                $salesOrder->lastModifiedDate ?? 'now'
            )
        );

        $this->eventManager->dispatch(
            'netsuite_order_import_save_before',
            [
                'netsuite_order' => $salesOrder,
                'magento_order' => $magentoOrder
            ]
        );

        $this->updateQuantities($magentoOrder, $salesOrder);

        $this->orderRepository->save($magentoOrder);

        $this->eventManager->dispatch(
            'netsuite_order_import_after',
            [
                'netsuite_order' => $salesOrder,
                'magento_order' => $magentoOrder
            ]
        );
    }

    /**
     * Lower the limit until the issue with slow order fetching is resolved
     *
     * @return int
     */
    protected function getRecordLimit(): int
    {
        return $this->recordLimit;
    }

    /**
     * Update qtyInvoiced and qtyShipped for magento order
     *
     * @param \Magento\Sales\Model\Order $magentoOrder
     * @param \NetSuite\Classes\SalesOrder $netsuiteOrder
     */
    private function updateQuantities($magentoOrder, $netsuiteOrder)
    {
        $netSuiteItems = [];
        foreach ($netsuiteOrder->itemList->item as $nsItem) {
            if (!empty($nsItem->item) && !empty($nsItem->item->internalId)) {
                $netSuiteItems[$nsItem->item->internalId] = [
                    'qty_invoiced' => (int)$nsItem->quantityBilled,
                    'qty_shipped' => (int)$nsItem->quantityFulfilled,
                ];
            }
        }

        $parentsToUpdate = [];
        foreach ($magentoOrder->getAllItems() as $item) {
            if ($item->getProductType() === Configurable::TYPE_CODE) {
                continue;
            }

            $nsProductId = $item->getProduct()->getNetsuiteInternalId();
            if (!$nsProductId) {
                continue;
            }

            if (array_key_exists($nsProductId, $netSuiteItems)) {
                $item->setQtyInvoiced($netSuiteItems[$nsProductId]['qty_invoiced']);
                $item->setQtyShipped($netSuiteItems[$nsProductId]['qty_shipped']);

                if ($item->getParentItemId()) {
                    $parentsToUpdate[$item->getParentItemId()] = $netSuiteItems[$nsProductId];
                }
            }
        }

        if (!empty($parentsToUpdate)) {
            foreach ($magentoOrder->getItems() as $item) {
                if ($item->isDeleted()) {
                    continue;
                }

                if (array_key_exists($item->getId(), $parentsToUpdate)
                    && $item->getProductType() === Configurable::TYPE_CODE) {
                    $item->setQtyInvoiced($parentsToUpdate[$item->getId()]['qty_invoiced']);
                    $item->setQtyShipped($parentsToUpdate[$item->getId()]['qty_shipped']);
                }
            }
        }
    }
}
