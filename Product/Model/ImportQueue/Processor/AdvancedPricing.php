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
namespace MageOS\NetSuiteConnector\Product\Model\ImportQueue\Processor;

use MageOS\NetSuiteConnector\Core\Model\ImportQueue\EntityProcessor;

class AdvancedPricing extends EntityProcessor
{
    public function process(string $entity): array
    {
        $this->preprocessAdvancedPricing();
        $this->importer->setValidationRequired(true);
        return parent::process($entity);
    }

    /**
     * @return void
     */
    private function preprocessAdvancedPricing(): void
    {
        $rawRows = $this->importRowList->getRawEntityData('advanced_pricing');
        $rawRows = $this->transformBundlePrices($rawRows);
        $this->importRowList->setRawEntityData('advanced_pricing', $rawRows);
    }

    /**
     * @param array $rows
     * @return array
     */
    public function transformBundlePrices(array $rows)
    {
        $res = [];
        $processedSkus = [];

        foreach ($rows as $row) {
            $sku = $row['sku'];
            if (isset($this->bundleSkus[$sku])) {
                $bundleParentId = $this->bundleSkus[$sku];
                if (!isset($processedSkus[$bundleParentId]) || $processedSkus[$bundleParentId] === $sku) {
                    $processedSkus[$bundleParentId] = $sku;

                    // duplicate tier price for the main bundle product
                    $res[] = [
                        'sku' => $bundleParentId,
                        'tier_price_website' => $row['tier_price_website'],
                        'tier_price_customer_group' => $row['tier_price_customer_group'],
                        'tier_price_qty' => $row['tier_price_qty'],
                        'tier_price' => $row['tier_price']
                    ];
                }
            }

            $res[] = $row;
        }

        return $res;
    }
}
