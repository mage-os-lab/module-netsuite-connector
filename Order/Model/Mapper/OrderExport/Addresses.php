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

use Magento\Directory\Api\Data\CountryInformationInterface;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Address as OrderAddress;
use NetSuite\Classes\Address;
use NetSuite\Classes\SalesOrder;

/**
 * This class adds billing and shipping addresses to NS order
 */
class Addresses
{
    public const DEFAULT_COUNTY_ID = 'US';

    /**
     * @var \MageOS\NetSuiteConnector\Core\Helper\Transform
     */
    private $transformHelper;

    /**
     * @var \Magento\Directory\Api\CountryInformationAcquirerInterface
     */
    private $countryInformationAcquirer;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @var \Magento\Sales\Api\OrderAddressRepositoryInterface
     */
    private $orderAddressRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var array
     */
    private $countriesInfo;

    /**
     * @param \MageOS\NetSuiteConnector\Core\Helper\Transform $transformHelper
     * @param \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformationAcquirer
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Sales\Api\OrderAddressRepositoryInterface $orderAddressRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Core\Helper\Transform $transformHelper,
        \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformationAcquirer,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Sales\Api\OrderAddressRepositoryInterface $orderAddressRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->transformHelper = $transformHelper;
        $this->countryInformationAcquirer = $countryInformationAcquirer;
        $this->eventManager = $eventManager;
        $this->orderAddressRepository = $orderAddressRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Add NS billing and shipping addresses to NS order
     *
     * @param SalesOrder $netsuiteOrder
     * @param OrderInterface $magentoOrder
     */
    public function addAddresses(SalesOrder $netsuiteOrder, OrderInterface $magentoOrder)
    {
        $netsuiteOrder->billingAddress = $this->createAddress($magentoOrder->getBillingAddress());

        if (!$magentoOrder->getIsVirtual()) {
            $addresses = $this->getOrderAddresses($magentoOrder);
            foreach ($addresses as $address) {
                if ($address->getAddressType() == OrderAddress::TYPE_SHIPPING) {
                    $netsuiteOrder->shippingAddress = $this->createAddress($address);
                }
            }
        } else {
            // For virtual orders push billing address as shipping for correct taxes
            $netsuiteOrder->shippingAddress = $this->createAddress($magentoOrder->getBillingAddress());
        }
    }

    /**
     * Create NS address record from given order address
     *
     * @param OrderAddressInterface $address
     * @return Address
     */
    public function createAddress(OrderAddressInterface $address): Address
    {
        $netsuiteAddress = new Address();
        $netsuiteAddress->addr1 = isset($address->getStreet()[0]) ? $address->getStreet()[0] : '';
        $netsuiteAddress->addr2 = isset($address->getStreet()[1]) ? $address->getStreet()[1] : '';
        $netsuiteAddress->city = $address->getCity();
        $country = $this->getCountryInfo($address->getCountryId());
        $netsuiteAddress->country = $this->transformHelper->transformCountryCode($country->getId());
        $netsuiteAddress->addressee = $address->getFirstname() . ' ' . $address->getLastname();
        $netsuiteAddress->addrPhone = $this->sanitizePhoneNumber($address->getTelephone());
        $netsuiteAddress->state = $address->getRegionCode();
        $netsuiteAddress->zip = $address->getPostcode();

        $this->eventManager->dispatch('netsuite_address_create_before', ['netsuite_address' => $netsuiteAddress]);
        return $netsuiteAddress;
    }

    /**
     * Load all order addresses
     *
     * @param OrderInterface $magentoOrder
     * @return OrderAddressInterface[]
     */
    private function getOrderAddresses(OrderInterface $magentoOrder): array
    {
        $this->searchCriteriaBuilder->addFilter('parent_id', $magentoOrder->getEntityId());
        $searchCriteria = $this->searchCriteriaBuilder->create();

        return $this->orderAddressRepository->getList($searchCriteria)->getItems();
    }

    /**
     * Get country info by country code
     *
     * Magento uses country code as ID. Return default country (US) if not found.
     *
     * @param string $countryId
     * @return CountryInformationInterface
     */
    private function getCountryInfo($countryId): CountryInformationInterface
    {
        $countries = $this->getCountriesInfo();
        foreach ($countries as $country) {
            if ($country->getId() == $countryId) {
                return $country;
            }
        }

        // Return default country to avoid exceptions
        foreach ($countries as $country) {
            if ($country->getId() == self::DEFAULT_COUNTY_ID) {
                return $country;
            }
        }
    }

    /**
     * Get information about all countries
     *
     * @return CountryInformationInterface[]
     */
    private function getCountriesInfo(): array
    {
        if (null === $this->countriesInfo) {
            $this->countriesInfo = $this->countryInformationAcquirer->getCountriesInfo();
        }
        return $this->countriesInfo;
    }

    /**
     * Replace all non-digits from phone number
     * If result is less then NS minimum (7) remove phone from request
     *
     * This is public to allow modifications by plugins
     *
     * @param $phoneNumber
     * @return string
     */
    public function sanitizePhoneNumber($phoneNumber): string
    {
        $phoneNumber = preg_replace('/[^\d]/', '', trim($phoneNumber??''));
        if (strlen($phoneNumber) < 7) {
            $phoneNumber = '';
        }
        return $phoneNumber;
    }
}
