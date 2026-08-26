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

namespace MageOS\NetSuiteConnector\ProductImages\Model\Process\Import\Product\Update;

use NetSuite\Classes\RecordType;

/**
 * This class is responsible for downloading specific product images from NS by image ID. Images are placed in magento
 * filesystem to directory pub/media/import
 */
class Downloader
{
    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\NetSuite\ServiceRepository
     */
    private $serviceRepository;

    /**
     * @var \MageOS\NetSuiteConnector\ProductImages\Model\Process\Import\Product\Update\Filesystem
     */
    private $filesystem;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @param \MageOS\NetSuiteConnector\Core\Model\NetSuite\ServiceRepository $serviceRepository
     * @param \MageOS\NetSuiteConnector\ProductImages\Model\Process\Import\Product\Update\Filesystem $filesystem
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\ServiceRepository $serviceRepository,
        \MageOS\NetSuiteConnector\ProductImages\Model\Process\Import\Product\Update\Filesystem $filesystem,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->serviceRepository = $serviceRepository;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
    }

    /**
     * Download images one-by-one from NS by internal IDs
     *
     * Images are placed in magento filesystem to directory pub/media/import
     *
     * @param array $nsImagesData
     * @return array
     */
    public function downloadImages($nsImagesData): array
    {
        $images = [];
        foreach ($nsImagesData as $key => $nsImageData) {
            try {
                $file = $this->serviceRepository->fetchRecordFromNetSuite(
                    RecordType::file,
                    (int)$nsImageData['internalId']
                );
                if (!empty($file->content) && !empty($file->name)) {
                    $this->filesystem->addImageToImportDir($nsImageData['name'], $file->content);
                    $images[$key] = $nsImageData['name'];
                }
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }

        return $images;
    }
}
