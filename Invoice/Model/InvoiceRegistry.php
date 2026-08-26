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

namespace MageOS\NetSuiteConnector\Invoice\Model;

use Magento\Sales\Api\Data\InvoiceInterface;

/**
 * This class is responsible to load invoices by NS internal ID. Loaded invoices are cached inside the class variable.
 */
class InvoiceRegistry
{
    /**
     * @var \Magento\Sales\Api\InvoiceRepositoryInterface
     */
    private $invoiceRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var array
     */
    private $invoiceCache = [];

    /**
     * @param \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->invoiceRepository = $invoiceRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Load magento invoice based on NS internal ID
     *
     * @param int $internalNetSuiteId
     * @return InvoiceInterface|null
     */
    public function getInvoiceByNetSuiteId($internalNetSuiteId)
    {
        if (isset($this->invoiceCache[$internalNetSuiteId])) {
            return $this->invoiceCache[$internalNetSuiteId];
        }

        $this->searchCriteriaBuilder->addFilter('netsuite_internal_id', $internalNetSuiteId);
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $magentoInvoices = $this->invoiceRepository->getList($searchCriteria)->getItems();

        if (\count($magentoInvoices)) {
            $this->invoiceCache[$internalNetSuiteId] = array_pop($magentoInvoices);
            return $this->invoiceCache[$internalNetSuiteId];
        }

        return null;
    }
}
