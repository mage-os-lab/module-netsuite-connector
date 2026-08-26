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
namespace MageOS\NetSuiteConnector\Product\Model;

class IndexerManagement
{
    public function __construct(
        private \Magento\Indexer\Model\IndexerFactory $indexerFactory,
        private \Magento\Indexer\Model\Indexer\CollectionFactory $indexerCollectionFactory
    ) {
    }

    public function changeIndexMode(bool $scheduled = true): void
    {
        $indexerCollection = $this->indexerCollectionFactory->create();
        $ids = $indexerCollection->getAllIds();
        foreach ($ids as $id) {
            $index = $this->indexerFactory->create()->load($id);
            $index->setScheduled($scheduled);
        }
    }

    public function reindexAll(): void
    {
        $indexerCollection = $this->indexerCollectionFactory->create();
        $ids = $indexerCollection->getAllIds();
        foreach ($ids as $id) {
            $index = $this->indexerFactory->create()->load($id);
            $index->reindexAll();
        }
    }
}
