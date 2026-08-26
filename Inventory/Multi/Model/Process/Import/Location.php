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

namespace MageOS\NetSuiteConnector\Inventory\Multi\Model\Process\Import;

use NetSuite\Classes\LocationSearchBasic;
use NetSuite\Classes\Record;
use NetSuite\Classes\SearchRequest;
use MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\CustomFieldAccess;
use MageOS\NetSuiteConnector\Core\Model\Process\Import\AbstractImportProcessor;
use MageOS\NetSuiteConnector\Inventory\Multi\Model\ConfigProvider\Permissions;
use MageOS\NetSuiteConnector\Inventory\Multi\Model\Mapper\ToMagento\Location as LocationMapper;

/**
 * Class Location - processor for location import
 * suppress phpmd coupling - reason AbstractImportProcessor that will be refactored
 * @suppressWarnings(PHPMD)
 */
class Location extends AbstractImportProcessor
{
    const MESSAGE_ACTION = 'location';

    private \MageOS\NetSuiteConnector\Inventory\Multi\Model\ConfigProvider\Permissions $permissions;
    private \MageOS\NetSuiteConnector\Inventory\Multi\Model\MagentoSourceRepository $magentoSourceRepository;
    private \Magento\InventoryApi\Api\Data\SourceInterfaceFactory $sourceInterfaceFactory;

    /**
     * Location constructor.
     * @param \MageOS\NetSuiteConnector\Inventory\Multi\Model\ConfigProvider\Permissions $permissionHelper
     * @param \Magento\Framework\Model\Context $context
     * @param \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement
     * @param Permissions $permissions
     * @param \MageOS\NetSuiteConnector\Inventory\Multi\Model\MagentoSourceRepository $magentoSourceRepository
     * @param \Magento\InventoryApi\Api\Data\SourceInterfaceFactory $sourceInterfaceFactory
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Inventory\Multi\Model\ConfigProvider\Permissions $permissionHelper,
        \Magento\Framework\Model\Context $context,
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement,
        \MageOS\NetSuiteConnector\Inventory\Multi\Model\ConfigProvider\Permissions $permissions,
        \MageOS\NetSuiteConnector\Inventory\Multi\Model\MagentoSourceRepository $magentoSourceRepository,
        \Magento\InventoryApi\Api\Data\SourceInterfaceFactory $sourceInterfaceFactory
    ) {
        parent::__construct($permissionHelper, $context, null, $serviceManagement);
        $this->permissions = $permissions;
        $this->magentoSourceRepository = $magentoSourceRepository;
        $this->sourceInterfaceFactory = $sourceInterfaceFactory;
    }

    public function getPermissionName(): string
    {
        return Permissions::IMPORT;
    }

    /**
     * we will have custom request to search Locations. so will not be used
     * @return string
     */
    public function getRecordType(): string
    {
        return self::MESSAGE_ACTION;
    }

    public function getMessageType(): string
    {
        return self::MESSAGE_ACTION;
    }

    /**
     * method checks condition to import location to queue
     * @param Record $location
     * @return bool
     * @suppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function isMagentoImportable(Record $location): bool
    {
        if (null === CustomFieldAccess::get($location, LocationMapper::CUST_FIELD_SOURCE_CODE)) {
            return false;
        }
        if (false === CustomFieldAccess::get($location, LocationMapper::CUST_FIELD_IMPORT)) {
            /**
             * we import changes even if entity is marked as NOT importable for cases when it is also
             * disabled in NetSuite so make sure it is already disabled in Magento.
             *
             * */
            $existedSource = $this->magentoSourceRepository
                ->getSourceByNetSuiteData((int)$location->internalId, null);
            if (null !== $existedSource
                && $existedSource->isEnabled()
                && false === CustomFieldAccess::get($location, LocationMapper::CUST_FIELD_ENABLE)) {
                return true;
            }
            return false;
        }
        return true;
    }

    /**
     * Due to the low number of the locations we do not check last update time.
     *
     * @param Record $location
     * @return bool
     * @suppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function isAlreadyImported(Record $location): bool
    {
        return false;
    }

    /**
     * we return false to avoid importing to Queue. this will be done as separate mode.
     */
    public function isActive(): bool
    {
        return false;
    }

    /**
     * method process netsuite location entity to create/update magento source
     * @param Record $location
     * @throws DataIntegrityException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Validation\ValidationException
     */
    public function process(Record $location)
    {
        $this->eventManager->dispatch(
            'netsuite_location_import_before',
            ['netsuite_location' => $location]
        );
        $locationMagentoCode = CustomFieldAccess::get($location, LocationMapper::CUST_FIELD_SOURCE_CODE);
        $existedSource = $this->magentoSourceRepository
            ->getSourceByNetSuiteData((int)$location->internalId, $locationMagentoCode);
        if (null === $existedSource) {
            $existedSource = $this->sourceInterfaceFactory->create();
        }
        $this->magentoSourceRepository->update($location, $existedSource);
        $this->eventManager->dispatch(
            'netsuite_location_import_after',
            ['netsuite_location' => $location, 'magento_source' => $existedSource]
        );
    }

    /**
     * @param string $recordType
     * @param string $startDateTime
     * @return SearchRequest
     * @throws \Exception
     * @suppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getNetsuiteRequest($recordType, string $startDateTime): SearchRequest
    {
        $tranSearchBasic = new LocationSearchBasic();
        $searchRequest = new SearchRequest();
        $searchRequest->searchRecord = $tranSearchBasic;

        return $searchRequest;
    }
}
