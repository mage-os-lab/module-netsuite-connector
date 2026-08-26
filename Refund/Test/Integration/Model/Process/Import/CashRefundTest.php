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
// phpcs:ignoreFile -- test file
namespace MageOS\NetSuiteConnector\Refund\Test\Integration\Model\Process\Import;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException;
use MageOS\NetSuiteConnector\Core\Test\Integration\Fixtures\Locator;
use MageOS\NetSuiteConnector\Core\Test\Integration\Model\NSRecordBuilder;
use MageOS\NetSuiteConnector\Refund\Model\MagentoCreditMemoRepository;
use MageOS\NetSuiteConnector\Refund\Model\Mapper\CreditMemo\CreditMemoFactory;

/**
 * Class CashRefundTest -
 * @magentoDbIsolation enabled
 */
class CashRefundTest extends \PHPUnit\Framework\TestCase
{

    private $objectManager;

    /**
     * Path to _files/_files_ns/... folders
     */
    const RELATIVE_PATH_TO_FIXTURES = '../../../';

    public static function setUpBeforeClass(): void
    {
        $fixturesUsed = [
            '_files/order.php',
            '_files/order_set_netsuite_id.php',
            '_files/order_rollback.php',
            '_files/address_data.php',
            '_files/product_simple.php',
            '_files/product_simple_rollback.php',
            '_files/default_rollback.php',
            '_files/invoice.php',
            '_files/invoice_rollback.php',
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
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testRecordWithoutOrderId()
    {
        $objectManager = Bootstrap::getObjectManager();

        // this does not have info about credit memo
        $creditMemo = NSRecordBuilder::aRecord(\NetSuite\Classes\CashRefund::class)
            ->withInternalId(1001)
            ->build();

        /** @var \MageOS\NetSuiteConnector\Refund\Model\Process\Import\Creditmemo $creditMemoImport */
        $creditMemoImport = $objectManager->get(\MageOS\NetSuiteConnector\Refund\Model\Process\Import\CashRefund::class);

        $this->assertTrue($creditMemoImport->isMagentoImportable($creditMemo));
        $this->assertFalse($creditMemoImport->isAlreadyImported($creditMemo));

        $this->expectException(DataIntegrityException::class);
        $this->expectExceptionMessage('[CreditMemoImport] Missed netsuite_internal_id for order');

        $creditMemoImport->process($creditMemo);
    }

    /**
     * @magentoDataFixtureBeforeTransaction ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/order.php
     * @magentoDataFixtureBeforeTransaction ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/invoice.php
     * @magentoAppIsolation enabled
     */
    public function testImportMemoByInvoice()
    {
        $creditMemo = $this->buildCreditMemoTestRecord(true, 11234);

        /** @var \MageOS\NetSuiteConnector\Refund\Model\Process\Import\CashRefund $creditMemoImport */
        $creditMemoImport = $this->objectManager->get(\MageOS\NetSuiteConnector\Refund\Model\Process\Import\CashRefund::class);
        $creditMemoRegistry = $this->objectManager->get(\MageOS\NetSuiteConnector\Refund\Model\MagentoCreditMemoRepository::class);

        $this->assertTrue($creditMemoImport->isMagentoImportable($creditMemo));
        $this->assertFalse($creditMemoImport->isAlreadyImported($creditMemo));

        $creditMemoImport->process($creditMemo);

        /** @var Creditmemo $memo */
        $memo = $creditMemoRegistry->getCreditmemoByNetSuiteId(1001);

        $this->assertInstanceOf(Creditmemo::class, $memo);
        $this->assertEquals('20.0000', $memo->getSubtotal());
        $this->assertEquals('1', $memo->getStoreId());
        $this->assertCount(1, $memo->getItems());
    }
    /**
     * @magentoDataFixtureBeforeTransaction ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/order.php
     * @magentoAppIsolation enabled
     */
    public function testImportMemoByOrder()
    {
        $creditMemo = $this->buildCreditMemoTestRecord(false, 11234);

        /** @var \MageOS\NetSuiteConnector\Refund\Model\Process\Import\CashRefund $creditMemoImport */
        $creditMemoImport = $this->objectManager->get(\MageOS\NetSuiteConnector\Refund\Model\Process\Import\CashRefund::class);
        $creditMemoRegistry = $this->objectManager->get(\MageOS\NetSuiteConnector\Refund\Model\MagentoCreditMemoRepository::class);

        $this->assertTrue($creditMemoImport->isMagentoImportable($creditMemo));
        $this->assertFalse($creditMemoImport->isAlreadyImported($creditMemo));

        $creditMemoImport->process($creditMemo);

        /** @var Creditmemo $memo */
        $memo = $creditMemoRegistry->getCreditmemoByNetSuiteId(1001);

        $this->assertInstanceOf(Creditmemo::class, $memo);
        $this->assertEquals('20.0000', $memo->getSubtotal());
        $this->assertEquals('1', $memo->getStoreId());
        $this->assertCount(1, $memo->getItems());
    }

    /**
     * Method build Test Creditmemo entry
     * @return \NetSuite\Classes\Record
     */
    private function buildCreditMemoTestRecord($inMagento, $orderId): \NetSuite\Classes\Record
    {
        $creditMemo = NSRecordBuilder::aRecord(\NetSuite\Classes\CashRefund::class)
            ->withInternalId(1001)
            ->withCreatedFrom(1001)
            ->withCMItemList(
                ['id' => 1, 'qty' => 2]
            )
            ->withTotal(20)
            ->withSubTotal(20)
            ->withLastModifiedDate("2020-03-11T03:13:14.000-07:00")
            ->customField(CreditMemoFactory::CUST_FIELD_REFUND_IN_MAGENTO, $inMagento)
            ->customField(MagentoCreditMemoRepository::CUST_FIELD_NS_ORDER_ID, $orderId)
            ->build();
        return $creditMemo;
    }
}
