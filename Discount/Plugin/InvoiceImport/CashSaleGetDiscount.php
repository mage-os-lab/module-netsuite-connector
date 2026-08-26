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

namespace MageOS\NetSuiteConnector\Discount\Plugin\InvoiceImport;

use NetSuite\Classes\CashSale;
use NetSuite\Classes\Record;
use MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException;
use MageOS\NetSuiteConnector\Discount\Model\Config\Source\LogicSwitcher;

class CashSaleGetDiscount
{
    private \MageOS\NetSuiteConnector\Discount\Model\Config\DiscountConfig $discountConfig;

    public function __construct(
        \MageOS\NetSuiteConnector\Discount\Model\Config\DiscountConfig $discountConfig
    ) {
        $this->discountConfig = $discountConfig;
    }

    /**
     * @param \MageOS\NetSuiteConnector\Invoice\Model\Mapper\ImportInvoice $subject
     * @param $result
     * @param CashSale|Record $cashSale
     * @return float|int
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws DataIntegrityException
     */
    public function afterGetDiscountAmount(
        \MageOS\NetSuiteConnector\Invoice\Model\Mapper\ImportInvoice $subject,
        $result,
        Record $cashSale
    ) {
        if ($this->discountConfig->getLogicSwitch() === LogicSwitcher::BODY) {
            $result = $cashSale->discountRate;
            if (strpos((string)$result, '%') !== false) {
                throw new DataIntegrityException(
                    'The CashSale Discount Rate is a percent value which is not supported'
                );
            }
            return $result;
        }

        $discountItemId = $this->discountConfig->getDiscountItemId();
        foreach ($cashSale->itemList->item as $item) {
            if ($item->item->internalId == $discountItemId) {
                return abs($item->amount);
            }
        }

        return $result;
    }
}
