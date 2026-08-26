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

namespace MageOS\NetSuiteConnector\Inventory\Multi\Model\Mapper\ToMagento;

use Magento\InventoryApi\Api\Data\SourceInterface;
use NetSuite\Classes\Location as NetSuiteLocation;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\CustomFieldAccess;
use MageOS\NetSuiteConnector\Inventory\Multi\Model\Mapper\ToMagento\Location as LocationMapper;

/**
 * Class Location provides transferring data between Netsuite Location and Magento Inventory Source entities.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Location
{
    /**
     * constants below - custom fields, that are part of RW Bundle. they will be created in NetSuite for the
     * module purpose
     */
    const CUST_FIELD_ENABLE = 'custrecord_rw_cf_enable_in_magento';
    const CUST_FIELD_IMPORT = 'custrecord_rw_cf_import_to_magento';
    const CUST_FIELD_SOURCE_CODE = 'custrecord_rw_cf_magento_code';
    const CUST_FIELD_PICKUP_DESCRIPTION = 'custrecord_rw_cf_magento_pickup_desc';
    const CUST_FIELD_PICKUP_NAME = 'custrecord_rw_cf_magento_pickup_name';

    private \MageOS\NetSuiteConnector\Core\Helper\Transform $transform;

    /**
     * Location constructor.
     * @param \MageOS\NetSuiteConnector\Core\Helper\Transform $transform
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Core\Helper\Transform $transform
    ) {
        $this->transform = $transform;
    }

    /**
     * we update only:
     * # expiration period
     * # discount amount
     * # status
     * other fields are ignored
     * @param SourceInterface $magentoSource
     * @param NetSuiteLocation $netsuiteLocation
     * @return void
     */
    public function mapToMagento(
        SourceInterface $magentoSource,
        NetSuiteLocation $netsuiteLocation
    ): void {
        $magentoSource->setName($netsuiteLocation->name);
        $magentoSource->setLatitude($netsuiteLocation->latitude);
        $magentoSource->setLongitude($netsuiteLocation->longitude);
        $magentoSource->setContactName($netsuiteLocation->mainAddress->addressee);
        $magentoSource->setPhone($netsuiteLocation->mainAddress->addrPhone ??
            $this->getDefaultPhone($netsuiteLocation));
        $magentoSource->setCity($netsuiteLocation->mainAddress->city);
        $magentoSource->setCountryId($this->transform->netsuiteCountryToCountryCode(
            $netsuiteLocation->mainAddress->country
        ));
        $magentoSource->setRegion($netsuiteLocation->mainAddress->state);
        $magentoSource->setPostcode($netsuiteLocation->mainAddress->zip);
        $magentoSource->setStreet($netsuiteLocation->mainAddress->addr1 . ' '
            . $netsuiteLocation->mainAddress->addr2 . ' ' . $netsuiteLocation->mainAddress->addr3);
        if (null === $magentoSource->getSourceCode()) {
            $magentoSource->setSourceCode(CustomFieldAccess::get($netsuiteLocation, self::CUST_FIELD_SOURCE_CODE));
        }
        $magentoSource->setEnabled(CustomFieldAccess::get($netsuiteLocation, self::CUST_FIELD_ENABLE));
        $magentoSource->setDescription(CustomFieldAccess::get(
            $netsuiteLocation,
            self::CUST_FIELD_PICKUP_DESCRIPTION
        ));
        if ($netsuiteLocation->locationType == '_store') {
            $extentionAttributes = $magentoSource->getExtensionAttributes();
            $extentionAttributes->setIsPickupLocationActive(true);
            $extentionAttributes->setFrontendName(CustomFieldAccess::get(
                $netsuiteLocation,
                self::CUST_FIELD_PICKUP_NAME
            ));
            $extentionAttributes->setFrontendDescription(CustomFieldAccess::get(
                $netsuiteLocation,
                self::CUST_FIELD_PICKUP_DESCRIPTION
            ));
            $magentoSource->setExtensionAttributes($extentionAttributes);
        }
        if (null === $magentoSource->getData('netsuite_internal_id')) {
            $magentoSource->setData('netsuite_internal_id', $netsuiteLocation->internalId);
        }
    }

    /**
     * separate method for further extending and using with plugins
     * @param NetSuiteLocation $netsuiteLocation
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getDefaultPhone(NetSuiteLocation $netsuiteLocation): string
    {
        return '1111111111';
    }
}
