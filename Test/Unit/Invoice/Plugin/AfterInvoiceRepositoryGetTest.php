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
 */

namespace MageOS\NetSuiteConnector\Test\Unit\Invoice\Plugin;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Api\Data\InvoiceExtension;
use Magento\Sales\Api\Data\InvoiceSearchResultInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order\Invoice;
use MageOS\NetSuiteConnector\Invoice\Plugin\AfterInvoiceRepositoryGet;
use MageOS\NetSuiteConnector\Invoice\Plugin\BeforeInvoiceRepositorySave;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Covers MageOS\NetSuiteConnector\Invoice\Plugin\AfterInvoiceRepositoryGet: a invoice read through the repository
 * has its NetSuite fields in the extension attributes, so saving it back keeps them.
 */
// Invoice's constructor dependencies are mocks created by Magento's ObjectManager helper
#[AllowMockObjectsWithoutExpectations]
class AfterInvoiceRepositoryGetTest extends TestCase
{
    public function testGetLoadsTheNetSuiteFieldsIntoTheExtensionAttributes(): void
    {
        $invoice = $this->createInvoice();

        $result = (new AfterInvoiceRepositoryGet())->afterGet($this->repository(), $invoice);

        $this->assertSame($invoice, $result);
        $this->assertSame('24769243', $invoice->getExtensionAttributes()->getNetsuiteInternalId());
    }

    public function testGetListLoadsTheNetSuiteFieldsOfEveryItem(): void
    {
        $first = $this->createInvoice();
        $second = $this->createInvoice();
        $second->setData('netsuite_internal_id', null);
        $searchResult = $this->createStub(InvoiceSearchResultInterface::class);
        $searchResult->method('getItems')->willReturn([$first, $second]);

        $result = (new AfterInvoiceRepositoryGet())->afterGetList($this->repository(), $searchResult);

        $this->assertSame($searchResult, $result);
        $this->assertSame('24769243', $first->getExtensionAttributes()->getNetsuiteInternalId());
        $this->assertNull($second->getExtensionAttributes()->getNetsuiteInternalId());
    }

    public function testSavingWhatGetReturnedKeepsTheNetSuiteId(): void
    {
        $invoice = $this->createInvoice();
        $repository = $this->repository();

        (new AfterInvoiceRepositoryGet())->afterGet($repository, $invoice);
        (new BeforeInvoiceRepositorySave())->beforeSave($repository, $invoice);

        $this->assertSame('24769243', $invoice->getData('netsuite_internal_id'));
    }

    private function createInvoice(): Invoice
    {
        /** @var Invoice $invoice */
        $invoice = (new ObjectManager($this))->getObject(Invoice::class);
        $invoice->setExtensionAttributes(new InvoiceExtension());
        $invoice->setData('netsuite_internal_id', '24769243');

        return $invoice;
    }

    private function repository(): InvoiceRepositoryInterface
    {
        return $this->createStub(InvoiceRepositoryInterface::class);
    }
}
