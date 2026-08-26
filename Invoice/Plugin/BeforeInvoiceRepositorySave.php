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

/**
 * This class transfers netsuite_internal_id from the extension data model to invoice model itself
 */
class BeforeInvoiceRepositorySave
{
    /**
     * Copy netsuite_internal_id from extension data object into target object
     *
     * @param InvoiceRepositoryInterface $subject
     * @param InvoiceInterface $invoice
     * @return array
     */
    public function beforeSave(InvoiceRepositoryInterface $subject, InvoiceInterface $invoice): array
    {
        $extensionAttribute = $invoice->getExtensionAttributes();
        if ($extensionAttribute && method_exists($extensionAttribute, 'getNetsuiteInternalId')) {
            $invoice->setNetsuiteInternalId($extensionAttribute->getNetsuiteInternalId());
        }
        return [$invoice];
    }
}
