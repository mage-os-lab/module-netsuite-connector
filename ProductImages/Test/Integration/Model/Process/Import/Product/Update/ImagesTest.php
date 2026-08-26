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
//phpcs:ignoreFile

namespace MageOS\NetSuiteConnector\ProductImages\Test\Integration\Model\Process\Import\Product\Update;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management;
use MageOS\NetSuiteConnector\Core\Test\Integration\Fixtures\Locator;

/**
 * Class ImagesTest - testing image import feature
 * @SuppressWarnings(PHPMD)
 */
class ImagesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \MageOS\NetSuiteConnector\Core\Helper\Data|\PHPUnit\Framework\MockObject\MockObject
     */
    private static $nsHelper;

    /**
     * @var \MageOS\NetSuiteConnector\Core\Test\Integration\Helper\NetSuiteServiceFaker
     */
    private static $netsuiteServiceFaker;

    /**
     * Path to _files/_files_ns/... folders
     */
    const RELATIVE_PATH_TO_FIXTURES = '../../../../../';

    public static function setUpBeforeClass():void
    {
        $fixturesUsed = [
            '_files/magento_image.jpg',
            '_files/magento_small_image.jpg',
            '_files/magento_thumbnail.jpg',
            '_files/test_image.jpg',
            '_files/test_image2.jpg',
            '_files/test_image3.jpg',
            '_files/product_image.php',
            '_files/product_image_rollback.php',
            '_files/product_simple.php',
            '_files/product_simple_rollback.php',
            '_files/product_with_image.php',
            '_files/product_with_image_rollback.php',
        ];

        $path = realpath(__DIR__ . "/" . self::RELATIVE_PATH_TO_FIXTURES) . "/";

        Locator::copy($path, $fixturesUsed);
    }

    /**
     * $netusiteServicerFaker is a replacement class for WSDL Netsuite class
     * $nsHelper is a mock because we use getNetsuiteService() call to get access to WSDL Netsuite class
     *
     * Because how Magento & phpunit works, we need to have them as static values. Main reason - the Mock is
     * cached but on the second test we create a new instance of the Mock but the old one is actually still active
     * in Magento code.
     */
    protected function setUp():void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->objectManager = $objectManager;

        if (!self::$netsuiteServiceFaker) {
            $path = realpath(__DIR__ . "/" . self::RELATIVE_PATH_TO_FIXTURES) . "/";
            self::$netsuiteServiceFaker = new \MageOS\NetSuiteConnector\Core\Test\Integration\Helper\NetSuiteServiceFaker($path);
        }

        if (!self::$nsHelper) {
            self::$nsHelper = $this->getMockBuilder(Management::class)
                ->onlyMethods(['get'])
                ->disableOriginalConstructor()
                ->getMock();
        }
        // this is important to run per each test
        $this->objectManager->configure([Management::class => ['shared' => true]]);
        $this->objectManager->addSharedInstance(self::$nsHelper, Management::class);
    }

    /**
     * @magentoDataFixtureBeforeTransaction ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/product_with_image.php
     * @magentoConfigFixture default/mageos_netsuite/products/base_image_attribute_ns_id base_image
     * @magentoConfigFixture default/mageos_netsuite/products/image_attribute_ns_ids base_image,additional_image_1,additional_image_2
     * @magentoConfigFixture default/mageos_netsuite/products/import_files_based_on_filename 1
     * @magentoDbIsolation enabled
     */
    public function testImportImages()
    {
        // see _files_ns_response/File_get
        $imageFilename = 'test_image.jpg';
        $imageFilename2 = 'test_image2.jpg';
        $imageFilename3 = 'test_image3.jpg';
        $parameters = [
            'netsuite_internal_id' => 0,
            'get_success' => 1,
            'has_files' => 1,
            'files' => [
                [
                    'file_name' => $imageFilename,
                    'file_content' => $this->getImageFileContent($imageFilename),
                ],
                [
                    'file_name' => $imageFilename2,
                    'file_content' => $this->getImageFileContent($imageFilename2),
                ],
                [
                    'file_name' => $imageFilename3,
                    'file_content' => $this->getImageFileContent($imageFilename3),
                ]
            ]
        ];
        self::$netsuiteServiceFaker->setParameters($parameters);
        $this->setNetSuiteServiceFaker();

        // key point is in the last several lines: 3 lines for custom fields with images AND the last line with
        // internal ID
        $nsProductDataJson = '{"createdDate":"2010-12-10T12:49:56.000-08:00","lastModifiedDate":"2020-03-11T03:13:14.000-07:00","purchaseDescription":"RS-232\/422\/485 to 100BaseFX Media Converter, ST Multimode","copyDescription":false,"expenseAccount":null,"dateConvertedToInv":null,"originalItemType":null,"originalItemSubtype":null,"cogsAccount":{"internalId":"180","externalId":null,"type":null,"name":"4500 Cost of Goods Sold : Purchases"},"intercoCogsAccount":null,"salesDescription":"test sales description","fraudRisk":null,"includeChildren":null,"incomeAccount":{"internalId":"54","externalId":null,"type":null,"name":"4000 Sales"},"intercoIncomeAccount":null,"taxSchedule":null,"dropshipExpenseAccount":null,"deferRevRec":null,"revenueRecognitionRule":null,"revRecForecastRule":null,"revenueAllocationGroup":null,"createRevenuePlansOn":null,"directRevenuePosting":null,"isTaxable":true,"matrixType":null,"assetAccount":{"internalId":"123","externalId":null,"type":null,"name":"1200 Inventory"},"matchBillToReceipt":false,"billQtyVarianceAcct":null,"billPriceVarianceAcct":null,"billExchRateVarianceAcct":null,"gainLossAccount":null,"shippingCost":null,"shippingCostUnits":null,"handlingCost":null,"handlingCostUnits":null,"weight":1,"weightUnit":"_lb","weightUnits":null,"costingMethodDisplay":"Average","unitsType":null,"stockUnit":null,"purchaseUnit":null,"saleUnit":null,"issueProduct":null,"billingSchedule":null,"trackLandedCost":false,"matrixItemNameTemplate":null,"isDropShipItem":false,"isSpecialOrderItem":false,"stockDescription":null,"deferredRevenueAccount":null,"producer":null,"manufacturer":"Signamax, Inc.","revRecSchedule":null,"mpn":"065-1063FSM","multManufactureAddr":null,"manufacturerAddr1":null,"manufacturerCity":null,"manufacturerState":null,"manufacturerZip":null,"countryOfManufacture":null,"roundUpAsComponent":null,"purchaseOrderQuantity":null,"purchaseOrderAmount":null,"purchaseOrderQuantityDiff":null,"receiptQuantity":null,"receiptAmount":null,"receiptQuantityDiff":null,"defaultItemShipMethod":null,"itemCarrier":null,"itemShipMethodList":null,"manufacturerTaxId":null,"scheduleBNumber":null,"scheduleBQuantity":null,"scheduleBCode":null,"manufacturerTariff":null,"preferenceCriterion":null,"minimumQuantity":null,"enforceMinQtyInternally":true,"minimumQuantityUnits":null,"softDescriptor":null,"shipPackage":null,"shipIndividually":false,"costCategory":null,"pricesIncludeTax":null,"purchasePriceVarianceAcct":null,"quantityPricingSchedule":null,"reorderPointUnits":null,"useMarginalRates":false,"preferredStockLevelUnits":null,"costEstimateType":"_averageCost","costEstimate":null,"transferPrice":null,"overallQuantityPricingType":"_byLineQuantity","pricingGroup":null,"vsoePrice":null,"vsoeSopGroup":null,"costEstimateUnits":null,"vsoeDeferral":null,"vsoePermitDiscount":null,"vsoeDelivered":null,"itemRevenueCategory":null,"preferredLocation":null,"isStorePickupAllowed":null,"reorderMultiple":null,"cost":null,"lastInvtCountDate":null,"nextInvtCountDate":null,"invtCountInterval":null,"invtClassification":null,"costUnits":null,"totalValue":null,"averageCost":null,"useBins":false,"quantityReorderUnits":null,"leadTime":null,"autoLeadTime":true,"lastPurchasePrice":null,"autoPreferredStockLevel":false,"preferredStockLevelDays":null,"safetyStockLevel":null,"safetyStockLevelDays":null,"backwardConsumptionDays":null,"seasonalDemand":false,"safetyStockLevelUnits":null,"demandModifier":null,"distributionNetwork":null,"distributionCategory":null,"autoReorderPoint":false,"storeDisplayName":null,"storeDisplayThumbnail":{"internalId":"23662","externalId":null,"type":null,"name":"RS-232-422-485-converter.gif"},"storeDisplayImage":{"internalId":"23662","externalId":null,"type":null,"name":"RS-232-422-485-converter.gif"},"storeDescription":null,"storeDetailedDescription":"test detailed description","storeItemTemplate":null,"pageTitle":"RS-232\/422\/485 to 100BaseFX Media Converter, ST Multimode,","metaTagHtml":"<meta name=\"keywords\" content=\"Superior Essex ,Signamax RS-232\/422\/485 to 100BaseFX Media Converter, ST Multimode ,065-1063FSM ,Cable , Outdoor Cable Low Voltage , Outdoor-Category 5e\"\/>","excludeFromSitemap":false,"sitemapPriority":"_auto","searchKeywords":"67732","isDonationItem":false,"showDefaultDonationAmount":false,"maxDonationAmount":null,"dontShowPrice":false,"noPriceMessage":null,"outOfStockMessage":null,"onSpecial":null,"outOfStockBehavior":"_default","relatedItemsDescription":null,"specialsDescription":null,"featuredDescription":null,"shoppingDotComCategory":null,"shopzillaCategoryId":null,"nexTagCategory":null,"urlComponent":"065-1063FSM","customForm":null,"itemId":"test item","upcCode":null,"displayName":null,"vendorName":null,"parent":null,"isOnline":false,"isHazmatItem":null,"hazmatId":null,"hazmatShippingName":null,"hazmatHazardClass":null,"hazmatPackingGroup":null,"hazmatItemUnits":null,"hazmatItemUnitsQty":null,"isGcoCompliant":null,"offerSupport":false,"isInactive":false,"availableToPartners":null,"department":null,"class":null,"location":null,"costingMethod":null,"currency":null,"preferredStockLevel":null,"pricingMatrix":{"pricing":[{"currency":null,"priceLevel":{"internalId":"1","externalId":null,"type":null,"name":"Base Price"},"discount":null,"priceList":{"price":[{"value":1111.11,"quantity":0}]}}],"replaceAll":false},"accountingBookDetailList":null,"purchaseTaxCode":null,"defaultReturnCost":null,"supplyReplenishmentMethod":null,"alternateDemandSourceItem":null,"fixedLotSize":null,"periodicLotSizeType":null,"supplyType":null,"demandTimeFence":null,"supplyTimeFence":null,"rescheduleInDays":null,"rescheduleOutDays":null,"periodicLotSizeDays":null,"supplyLotSizingMethod":null,"forwardConsumptionDays":null,"demandSource":null,"quantityBackOrdered":null,"quantityCommitted":null,"quantityAvailable":null,"quantityOnHand":1111,"onHandValueMli":null,"quantityOnOrder":null,"rate":null,"reorderPoint":null,"quantityCommittedUnits":null,"salesTaxCode":null,"quantityAvailableUnits":null,"quantityOnHandUnits":null,"vendor":null,"quantityOnOrderUnits":null,"productFeedList":null,"subsidiaryList":null,"itemOptionsList":null,"itemVendorList":{"itemVendor":[{"vendor":{"internalId":"709","externalId":null,"type":null,"name":"Signamax, Inc."},"vendorCode":"065-1063FSM","vendorCurrencyName":null,"vendorCurrency":null,"purchasePrice":837,"preferredVendor":true,"schedule":null,"subsidiary":null}],"replaceAll":true},"siteCategoryList":{"siteCategory":[{"website":null,"itemId":null,"parentCategory":null,"categoryListLayout":null,"itemListLayout":null,"relatedItemsListLayout":null,"correlatedItemsListLayout":null,"isOnline":null,"isInactive":null,"description":null,"storeDetailedDescription":null,"storeDisplayThumbnail":null,"storeDisplayImage":null,"pageTitle":null,"metaTagHtml":null,"excludeFromSitemap":null,"urlComponent":null,"sitemapPriority":null,"searchKeywords":null,"presentationItemList":null,"translationsList":null,"internalId":null,"externalId":null,"nullFieldList":null,"category":{"internalId":"53696","externalId":null,"type":null,"name":"Networking : Media Converters : Serial Data to Ethernet Device Servers\/Media Converters (removed from website)"},"isDefault":false,"categoryDescription":"test"}],"replaceAll":true},"translationsList":null,"binNumberList":null,"locationsList":{"locations":[{"location":"CWT Main Warehouse","quantityOnHand":null,"onHandValueMli":null,"averageCostMli":null,"lastPurchasePriceMli":null,"reorderPoint":0,"locationAllowStorePickup":null,"locationQtyAvailForStorePickup":null,"preferredStockLevel":0,"leadTime":null,"defaultReturnCost":null,"safetyStockLevel":null,"cost":null,"inventoryCostTemplate":null,"buildTime":null,"lastInvtCountDate":null,"nextInvtCountDate":null,"isWip":null,"invtCountInterval":null,"invtClassification":null,"costingLotSize":null,"quantityOnOrder":null,"quantityCommitted":null,"quantityAvailable":0,"quantityBackOrdered":null,"locationId":{"internalId":"1","externalId":null,"type":null,"name":null},"supplyReplenishmentMethod":null,"alternateDemandSourceItem":null,"fixedLotSize":null,"periodicLotSizeType":null,"periodicLotSizeDays":null,"supplyType":null,"supplyLotSizingMethod":null,"demandSource":null,"backwardConsumptionDays":null,"forwardConsumptionDays":null,"demandTimeFence":null,"supplyTimeFence":null,"rescheduleInDays":null,"rescheduleOutDays":null}],"replaceAll":true},"matrixOptionList":null,"presentationItemList":null,
"customFieldList":{"customField":[
{"value":"In Stock","internalId":"7","scriptId":"custitem6"},
{"value":"New","internalId":"8","scriptId":"custitem7"},
{"value":"1","internalId":"9","scriptId":"custitem8"},
{"value":{"name":"Test Value","internalId":"4","externalId":null,"typeId":"319"},"internalId":"1207","scriptId":"custitem_test_select"},
{"value":false,"internalId":"1016","scriptId":"test_attribute_checkbox"},
{"value":2222.22,"internalId":"599","scriptId":"custitem_test_price"},
{"value":{"name":"test_image.jpg","internalId":"11111","externalId":null,"typeId":"111111"},"internalId":"1111","scriptId":"base_image"},
{"value":{"name":"test_image2.jpg","internalId":"11112","externalId":null,"typeId":"111112"},"internalId":"1112","scriptId":"additional_image_1"},
{"value":{"name":"test_image3.jpg","internalId":"11113","externalId":null,"typeId":"111113"},"internalId":"1113","scriptId":"additional_image_2"}
]},"externalId":null,"nullFieldList":null,
"internalId":"1"}';
        $nsProductData = json_decode($nsProductDataJson, true);
        $nsProduct = new \NetSuite\Classes\InventoryItem();
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\FromJSON::transform($nsProduct, $nsProductData);

        /** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $product = $productRepository->getById(1);

        /** @var \MageOS\NetSuiteConnector\ProductImages\Model\Process\Import\Product\Update\Images $imageImport */
        $imageImport = $this->objectManager->get(\MageOS\NetSuiteConnector\ProductImages\Model\Process\Import\Product\Update\Images::class);
        $imageImport->process($product, $nsProduct);

        $this->assertEquals($imageFilename, $product->getData('base_image'));
        $this->assertEquals($imageFilename, $product->getData('small_image'));
        $this->assertEquals($imageFilename, $product->getData('thumbnail'));
        $this->assertEquals($imageFilename2 . ',' . $imageFilename3, $product->getData('additional_images'));

        /** @var $mediaDirectory \Magento\Framework\Filesystem\Directory\WriteInterface */
        $mediaDirectory = $this->objectManager->get(\Magento\Framework\Filesystem::class)
            ->getDirectoryWrite(DirectoryList::MEDIA);

        $this->assertFileExists($mediaDirectory->getAbsolutePath() . '/import/' . $imageFilename);
        $this->assertFileExists($mediaDirectory->getAbsolutePath() . '/import/' . $imageFilename2);
        $this->assertFileExists($mediaDirectory->getAbsolutePath() . '/import/' . $imageFilename3);
    }

    /**
     * Helper methods for tests
     */
    private function setNetSuiteServiceFaker()
    {
        self::$nsHelper->method('get')
            ->willReturn(self::$netsuiteServiceFaker);
    }

    private function getImageFileContent($filename)
    {
        $absoluteFilename = realpath(__DIR__ . '/' . self::RELATIVE_PATH_TO_FIXTURES) . '/_files/' . $filename;
        if (!is_file($absoluteFilename)) {
            throw new \Exception('Image file ' . $filename . ' does not exist');
        }
        $fp = fopen($absoluteFilename, 'r');
        return fread($fp, 10000000);
    }
}
