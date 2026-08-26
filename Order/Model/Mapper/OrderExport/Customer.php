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

namespace MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport;

use Magento\Sales\Api\Data\OrderInterface;
use NetSuite\Classes\SalesOrder;
use NetSuite\Classes\RecordRef;
use NetSuite\Classes\RecordType;
use MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException;

/**
 * This class adds customer to NS order
 */
class Customer
{
    /**
     * @var \MageOS\NetSuiteConnector\Customer\Model\Mapper\Customer
     */
    private $customerMapperHelper;

    /**
     * Customer constructor.
     * @param \MageOS\NetSuiteConnector\Customer\Model\Mapper\Customer $customerMapperHelper
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Customer\Model\Mapper\Customer $customerMapperHelper
    ) {
        $this->customerMapperHelper = $customerMapperHelper;
    }

    /**
     * Add NS customer to NS order
     *
     * @param SalesOrder $netsuiteOrder
     * @param OrderInterface $magentoOrder
     */
    public function addCustomer(SalesOrder $netsuiteOrder, OrderInterface $magentoOrder)
    {
        $netsuiteOrder->entity = $this->createCustomer($this->getNetsuiteCustomerId($magentoOrder));
    }

    /**
     * Get NS customer ID for given order
     *
     * @param OrderInterface $magentoOrder
     * @return mixed
     * @throws \Exception
     */
    private function getNetsuiteCustomerId($magentoOrder)
    {
        $netsuiteCustomerId = $this->customerMapperHelper->createNetsuiteCustomerFromOrder($magentoOrder);
        if (!$netsuiteCustomerId) {
            throw new DataIntegrityException("Could not find / create the netsuite customer externalIdString="
                . $magentoOrder->getCustomerId());
        }
        return $netsuiteCustomerId;
    }

    /**
     * Create NS customer record from given customer netsuite ID
     *
     * @param int $netsuiteCustomerId
     * @return RecordRef
     */
    private function createCustomer($netsuiteCustomerId): RecordRef
    {
        $netsuiteCustomer = new RecordRef();
        $netsuiteCustomer->type = RecordType::customer;
        $netsuiteCustomer->internalId = $netsuiteCustomerId;
        return $netsuiteCustomer;
    }
}
