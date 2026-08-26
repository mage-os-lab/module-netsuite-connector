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
namespace MageOS\NetSuiteConnector\Inventory\Service\PostProcess;

use MageOS\NetSuiteConnector\Core\Api\Data\MonitorItemInterface;
use MageOS\NetSuiteConnector\Core\Enum\Message\Status;
use MageOS\NetSuiteConnector\Core\Model\Monitor\Data\Process;
use MageOS\NetSuiteConnector\Order\Model\Process\Export\OrderPlace;

/**
 * This class add compensation for reservations after order export to NetSuite.
 * suppress phpmd coz logic get from core (coupling problem).
 * @SuppressWarnings(PHPMD)
 */
class CompensateReservation implements \MageOS\NetSuiteConnector\Core\Api\PostProcessHandlerInterface
{
    public function __construct(
        private \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        private \MageOS\NetSuiteConnector\Inventory\Model\Sales\Order\InventoryReservation $inventoryReservation
    ) {
    }

    /**
     * Method adds compensation for reservations after order export
     */
    public function process(MonitorItemInterface $message, Status $status): void
    {
        if ($status->equals(Status::DONE()) || $status->equals(Status::ERROR())) {
            $process = $message->getProcess();
            $entity = $message->getEntity();

            if ($process->equals(Process::EXPORT()) && $entity === OrderPlace::MESSAGE_ACTION) {
                $order = $this->orderRepository->get($message->getItemId());
                $this->inventoryReservation->execute($order);
            }
        }
    }
}
