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

namespace MageOS\NetSuiteConnector\CustomerImport\Model\Mapper\Customer\Address;

use Magento\Customer\Api\Data\AddressInterface;
use NetSuite\Classes\Address as NetSuiteAddress;

class Map
{
    private \MageOS\NetSuiteConnector\Core\Helper\Transform $addressTransformer;
    private \MageOS\NetSuiteConnector\Customer\Model\Mapper\Address $customerMapper;
    private \MageOS\NetSuiteConnector\CustomerImport\Model\Config\CustomerImportConfig $customerImportConfig;
    private \Magento\Customer\Api\Data\RegionInterfaceFactory $regionFactory;

    public function __construct(
        \Magento\Customer\Api\Data\RegionInterfaceFactory $regionFactory,
        \MageOS\NetSuiteConnector\Core\Helper\Transform $addressTransformer,
        \MageOS\NetSuiteConnector\Customer\Model\Mapper\Address $customerMapper,
        \MageOS\NetSuiteConnector\CustomerImport\Model\Config\CustomerImportConfig $customerImportConfig
    ) {
        $this->addressTransformer = $addressTransformer;
        $this->customerMapper = $customerMapper;
        $this->customerImportConfig = $customerImportConfig;
        $this->regionFactory = $regionFactory;
    }

    public function mapNetSuiteToMagento(NetSuiteAddress $nsAddress, AddressInterface $magentoAddress): void
    {
        //location details
        $postcode = !empty($nsAddress->zip) ? $nsAddress->zip : $this->getDefaultZip($nsAddress);
        $magentoAddress->setPostcode($postcode);

        $magentoAddress->setCountryId($this->getCountry($nsAddress));
        $region = $this->getRegion($nsAddress);
        if ($region) {
            $magentoAddress->setRegionId($region->getRegionId());
            $magentoAddress->setRegion($region);
        }

        $city = !empty($nsAddress->city) ? $nsAddress->city : $this->getDefaultCity($nsAddress);
        $magentoAddress->setCity($city);

        $street = $this->addressTransformer->netsuiteAddressToStreet($nsAddress);
        $street = !empty($street) ? $street : $this->getDefaultStreet($nsAddress);
        $magentoAddress->setStreet($street);

        $phone = $this->customerMapper->sanitizePhoneNumber($nsAddress->addrPhone);
        $phone = !empty($phone) ? $phone : $this->getDefaultPhone($nsAddress);
        $magentoAddress->setTelephone($phone);
    }

    /**
     * Exposing the getter publicly to allow easy
     * plugin access and modification if needed.
     */
    public function getRegion(NetSuiteAddress $address): ?\Magento\Customer\Api\Data\RegionInterface
    {
        /** @var \Magento\Directory\Model\Region $region */
        $region = $this->addressTransformer->netsuiteStateToRegionId(
            $this->addressTransformer->netsuiteCountryToCountryCode(
                (string)$address->country
            ),
            $address->state,
            true
        );
        if (!$region) {
            return null;
        }

        $regionData = $this->regionFactory->create();
        $regionData->setRegionId($region->getId())
            ->setRegion($region->getDefaultName())
            ->setRegionCode($region->getCode());

        return $regionData;
    }

    /**
     * Exposing the getter publicly to allow easy
     * plugin access and modification if needed.
     */
    public function getCountry(NetSuiteAddress $address): string
    {
        return $address->country ?
            $this->addressTransformer->netsuiteCountryToCountryCode((string)$address->country)
            : $this->customerImportConfig->getDefaultAddressCountry();
    }

    /**
     * Exposing the getter publicly and adding unused parameter to allow easy
     * plugin access and modification if needed.
     *
     * @suppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getDefaultZip(NetSuiteAddress $address): string
    {
        return $this->customerImportConfig->getDefaultAddressZip();
    }

    /**
     * Exposing the getter publicly and adding unused parameter to allow easy
     * plugin access and modification if needed.
     *
     * @suppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getDefaultCity(NetSuiteAddress $address): string
    {
        return $this->customerImportConfig->getDefaultAddressCity();
    }

    /**
     * Exposing the getter publicly and adding unused parameter to allow easy
     * plugin access and modification if needed.
     *
     * @suppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getDefaultPhone(NetSuiteAddress $address): string
    {
        return $this->customerImportConfig->getDefaultAddressPhone();
    }

    /**
     * Exposing the getter publicly and adding unused parameter to allow easy
     * plugin access and modification if needed.
     *
     * @suppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getDefaultStreet(NetSuiteAddress $address): array
    {
        return [$this->customerImportConfig->getDefaultAddressStreet()];
    }
}
