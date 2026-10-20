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
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order\Invoice;
use MageOS\NetSuiteConnector\Invoice\Plugin\BeforeInvoiceRepositorySave;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Covers MageOS\NetSuiteConnector\Invoice\Plugin\BeforeInvoiceRepositorySave: saving a invoice through the repository
 * must not clear its NetSuite id when the extension attributes do not have it.
 */
// Invoice's constructor dependencies are mocks created by Magento's ObjectManager helper
#[AllowMockObjectsWithoutExpectations]
class BeforeInvoiceRepositorySaveTest extends TestCase
{
    public function testItKeepsTheNetSuiteIdWhenTheExtensionAttributesDoNotHaveIt(): void
    {
        $invoice = $this->createInvoice();
        $invoice->setData('netsuite_internal_id', '24769243');

        $this->invoke($invoice);

        $this->assertSame('24769243', $invoice->getData('netsuite_internal_id'));
    }

    public function testItCopiesTheNetSuiteIdFromTheExtensionAttributes(): void
    {
        $invoice = $this->createInvoice();
        $invoice->getExtensionAttributes()->setNetsuiteInternalId('24769243');

        $this->invoke($invoice);

        $this->assertSame('24769243', $invoice->getData('netsuite_internal_id'));
    }

    private function createInvoice(): Invoice
    {
        /** @var Invoice $invoice */
        $invoice = (new ObjectManager($this))->getObject(Invoice::class);
        $invoice->setExtensionAttributes(new InvoiceExtension());

        return $invoice;
    }

    private function invoke(Invoice $invoice): void
    {
        (new BeforeInvoiceRepositorySave())->beforeSave($this->createStub(InvoiceRepositoryInterface::class), $invoice);
    }
}
