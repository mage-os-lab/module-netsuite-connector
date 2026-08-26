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

use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use NetSuite\Classes\Record;
use NetSuite\Classes\Customer;

/**
 * This class adds address information to the magento shipment based on the data from netsuite fulfillment
 */
class Address
{
    private \MageOS\NetSuiteConnector\Customer\Model\Mapper\Address $addressMapper;
    private \Magento\Sales\Api\OrderAddressRepositoryInterface $orderAddressRepository;

    /**
     * Address constructor.
     * @param \MageOS\NetSuiteConnector\Customer\Model\Mapper\Address $addressMapper
     * @param \Magento\Sales\Api\OrderAddressRepositoryInterface $orderAddressRepository
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Customer\Model\Mapper\Address $addressMapper,
        \Magento\Sales\Api\OrderAddressRepositoryInterface $orderAddressRepository
    ) {
        $this->addressMapper = $addressMapper;
        $this->orderAddressRepository = $orderAddressRepository;
    }

    /**
     * Create and add shipping address based on netsuiteShipment data from NetSuite
     *
     * @param Record $netsuiteShipment
     * @param Customer $netsuiteCustomer
     * @param OrderInterface $magentoOrder
     * @return OrderAddressInterface
     */
    public function addShippingAddress(
        Record $netsuiteShipment,
        Customer $netsuiteCustomer,
        OrderInterface $magentoOrder
    ): OrderAddressInterface {
        $magentoShippingAddress = $this->addressMapper->getAddressMagentoFormatFromNetsuiteAddress(
            $netsuiteShipment->shippingAddress,
            $netsuiteCustomer,
            $magentoOrder
        );

        $magentoShippingAddress->setEntityId($magentoOrder->getShippingAddressId());
        $magentoShippingAddress->setAddressType('shipping');
        $this->orderAddressRepository->save($magentoShippingAddress);
        return $magentoShippingAddress;
    }
}
