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

namespace MageOS\NetSuiteConnector\Product\Model\Prefetch;

use Magento\Framework\DataObject;

class ProductPrefetchIdSource implements PrefetchIdSourceInterface
{
    /**
     * @var PrefetchIdSourceInterface[]
     */
    private $sources;

    /**
     * @param PrefetchIdSourceInterface[] $sources
     */
    public function __construct(
        ?array $sources = null
    ) {
        $this->sources = $sources;
    }

    /**
     * Query all prefetch sources and return a list of unique internal IDs needed to be prefetched
     * @param \NetSuite\Classes\Record[] $records
     * @return string[]
     * @throws \RuntimeException
     */
    public function execute(array $records)
    {
        $internalIds = [];

        foreach ($this->sources as $sourceName => $source) {
            try {
                $internalIds = array_unique(
                    // phpcs:ignore
                    array_merge(
                        $internalIds,
                        $source->execute($records)
                    )
                );
            } catch (\Exception $e) {
                throw new \RuntimeException(
                    'Error querying ' . $sourceName . ' source for IDs to prefetch: ' . $e->getMessage()
                );
            }
        }

        return $internalIds;
    }

    /**
     * @inheritdoc
     */
    public function mapToProduct(string $internalId, DataObject $product)
    {
        foreach ($this->sources as $source) {
            $source->mapToProduct($internalId, $product);
        }
    }

    /**
     * @inheritdoc
     */
    public function cleanup()
    {
        foreach ($this->sources as $source) {
            $source->cleanup();
        }
    }
}
