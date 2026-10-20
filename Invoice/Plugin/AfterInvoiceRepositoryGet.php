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

namespace MageOS\NetSuiteConnector\Invoice\Plugin;

use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\InvoiceSearchResultInterface;

/**
 * This class loads the NetSuite fields of the invoice into its extension attributes, so they are always present
 * and saving the invoice through the repository keeps them
 */
class AfterInvoiceRepositoryGet
{
    /**
     * Load the NetSuite fields into the extension attributes of the invoice
     *
     * @param InvoiceRepositoryInterface $subject
     * @param InvoiceInterface $invoice
     * @return InvoiceInterface
     * @SuppressWarnings("unused")
     */
    public function afterGet(InvoiceRepositoryInterface $subject, InvoiceInterface $invoice): InvoiceInterface
    {
        $this->loadInvoice($invoice);
        return $invoice;
    }

    /**
     * Load the NetSuite fields into the extension attributes of every invoice in the result
     *
     * @param InvoiceRepositoryInterface $subject
     * @param InvoiceSearchResultInterface $searchResult
     * @return InvoiceSearchResultInterface
     * @SuppressWarnings("unused")
     */
    public function afterGetList(
        InvoiceRepositoryInterface $subject,
        InvoiceSearchResultInterface $searchResult
    ): InvoiceSearchResultInterface {
        foreach ($searchResult->getItems() as $invoice) {
            $this->loadInvoice($invoice);
        }
        return $searchResult;
    }

    /**
     * Copy the NetSuite fields of the invoice into its extension attributes
     *
     * @param InvoiceInterface $invoice
     * @return void
     */
    private function loadInvoice(InvoiceInterface $invoice): void
    {
        $extensionAttributes = $invoice->getExtensionAttributes();
        if (!$extensionAttributes) {
            return;
        }
        $extensionAttributes->setNetsuiteInternalId($invoice->getData('netsuite_internal_id'));
    }
}
