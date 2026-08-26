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

use Magento\Catalog\Api\Data\ProductInterface;

/**
 * This class is responsible for operation with product gallery: clean up AND check existing images
 */
class Gallery
{
    /**
     * @var \MageOS\NetSuiteConnector\ProductImages\Model\Config\ImageConfig
     */
    private $imageConfig;

    /**
     * @var \MageOS\NetSuiteConnector\ProductImages\Model\Process\Import\Product\Update\Filesystem
     */
    private $filesystem;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @param \MageOS\NetSuiteConnector\ProductImages\Model\Config\ImageConfig $imageConfig
     * @param \MageOS\NetSuiteConnector\ProductImages\Model\Process\Import\Product\Update\Filesystem $filesystem
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \MageOS\NetSuiteConnector\ProductImages\Model\Config\ImageConfig $imageConfig,
        \MageOS\NetSuiteConnector\ProductImages\Model\Process\Import\Product\Update\Filesystem $filesystem,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->imageConfig = $imageConfig;
        $this->filesystem = $filesystem;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

    /**
     * Remove existing product images before importing new gallery
     *
     * @param ProductInterface $product
     * @param array $nsImagesData
     * @throws \Exception
     */
    public function cleanUpMediaGallery(ProductInterface $product, $nsImagesData): void
    {
        try {
            $mediaGallery = $originalMediaGallery = $product->getMediaGalleryEntries();
            foreach ($mediaGallery as $key => $entry) {
                $removeFile = true;
                foreach ($nsImagesData as $nsImageData) {
                    if ($entry->getFile() === null
                        || (strpos($entry->getFile(), strtolower($nsImageData['name'])) !== false
                            && $this->imageConfig->getImportFilesBasedOnFilename())
                    ) {
                        $removeFile = false;
                        break;
                    }
                }
                if ($removeFile) {
                    unset($mediaGallery[$key]);
                    $this->filesystem->deleteImageFromMediaGallery($entry->getFile());
                }
            }

            // We don't need to save the product if no changes occurred.
            if (count($originalMediaGallery) != count($mediaGallery)) {
                $product->setMediaGalleryEntries($mediaGallery);
                $this->productRepository->save($product);
            }
        } catch (\Exception $e) {
            // phpcs:enable
            $this->logger->error($e->getMessage());
            throw $e;
        }
    }

    /**
     * Check whether the product image exists by given name
     *
     * If enabled validation by name we check:
     * 1.netSuite Image file got same name
     * 2.if they match we do not import file
     * if disable we will reload the file
     *
     * @param ProductInterface $product
     * @param string $nsFileName
     * @return bool
     */
    public function isImageImported(ProductInterface $product, string $nsFileName): bool
    {
        $result = false;
        if (!$this->imageConfig->getImportFilesBasedOnFilename()) {
            return $result;
        }
        foreach ($product->getMediaGalleryEntries() as $entry) {
            if ($entry->getFile() !== null && false !== strpos($entry->getFile(), strtolower($nsFileName))) {
                $result = true;
                break;
            }
        }
        return $result;
    }
}
