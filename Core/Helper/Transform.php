<?php
declare(strict_types=1);
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
namespace MageOS\NetSuiteConnector\Core\Helper;

class Transform
{
    /*
     * For some data, Netsuite is using enumarations.
     * For example, setting a country requires respecting the standard defined in the Country class.
     * Sadly, the conventions are just ad-hoc conventions even for things like countries where ISOs exist.
     * This class provides utility function that try to convert Magento "enums" to Netsuite ones.
     */
    /**
     * @var \Magento\Directory\Model\Region
     */
    private $regionHelper;
    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\Config\EtcXmlResolver
     */
    private $etcXmlResolver;

    /**
     * Transform constructor.
     * @param \MageOS\NetSuiteConnector\Core\Model\Config\EtcXmlResolver $etcXmlResolver
     * @param \Magento\Directory\Model\Region $region
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Core\Model\Config\EtcXmlResolver $etcXmlResolver,
        \Magento\Directory\Model\Region $region
    ) {
        $this->regionHelper = $region;
        $this->etcXmlResolver = $etcXmlResolver;
    }

    //receives a ISO country code, transforms it in "Netsuite" format. US -> _unitedStates
    public function transformCountryCode(string $countryCode): ?string
    {
        $countries = $this->etcXmlResolver->getXml('countries.xml');

        foreach ($countries->country as $countryObject) {
            if ($countryObject->code == $countryCode) {
                return (string)$countryObject->name;
            }
        }
        return null;
    }

    public function netsuiteCountryToCountryCode(string $netsuiteCountryName): ?string
    {
        $countries = $this->etcXmlResolver->getXml('countries.xml');

        foreach ($countries->country as $countryObject) {
            if ((string)$countryObject->name === $netsuiteCountryName) {
                return (string)$countryObject->code;
            }
        }
        return null;
    }

    /**
     * @param $country string ISO 3166-1 alpha-2 country code
     * @param $state string State name as appears in NetSuite
     * @return int
     */
    public function netsuiteStateToRegionId($country, $state, $returnObject = false)
    {
        $region = $this->regionHelper->loadByCode($state, $country);

        if ($region !== null) {
            return $returnObject ? $region : $region->getId();
        }

        // @todo something in case if region is not found

        return null;
    }

    /**
     * Transform address from 3-line netsuite entry to Magento 2-line suitable for AddressInterface
     * @param $nsAddress \NetSuite\Classes\CustomerAddressbook
     * @return string[]
     */
    public function netsuiteAddressToStreet(\NetSuite\Classes\Address $nsAddress): array
    {
        $street = [];

        if (!empty($nsAddress->addr1)) {
            $street[] = $nsAddress->addr1;
        }
        if (!empty($nsAddress->addr2)) {
            $street[] = $nsAddress->addr2;
        }
        if (!empty($nsAddress->addr3)) {
            $street[] = $nsAddress->addr3;
        }

        if (\count($street) === 3) {
            return [$street[0], $street[1] . ' ' . $street[2]];
        } else {
            return $street;
        }
    }
}
