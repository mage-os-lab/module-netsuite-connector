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
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use NetSuite\Classes\Record;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\CustomFieldAccess;

/**
 * This class loads product images from NS for specific NS product and add them to magento product. Existing images
 * will be removed.
 */
class Images
{
    /**
     * @var \MageOS\NetSuiteConnector\ProductImages\Model\Config\ImageConfig
     */
    private $imageConfig;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \MageOS\NetSuiteConnector\ProductImages\Model\Process\Import\Product\Update\Gallery
     */
    private $gallery;

    /**
     * @var \MageOS\NetSuiteConnector\ProductImages\Model\Process\Import\Product\Update\Downloader
     */
    private $downloader;

    /**
     * @var \MageOS\NetSuiteConnector\ProductImages\Model\Process\Import\Product\Update\Filesystem
     */
    private $filesystem;

    /**
     * @param \MageOS\NetSuiteConnector\ProductImages\Model\Config\ImageConfig $imageConfig
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \MageOS\NetSuiteConnector\ProductImages\Model\Process\Import\Product\Update\Gallery $gallery
     * @param \MageOS\NetSuiteConnector\ProductImages\Model\Process\Import\Product\Update\Downloader $downloader
     * @param \MageOS\NetSuiteConnector\ProductImages\Model\Process\Import\Product\Update\Filesystem $filesystem
     */
    public function __construct(
        \MageOS\NetSuiteConnector\ProductImages\Model\Config\ImageConfig $imageConfig,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \MageOS\NetSuiteConnector\ProductImages\Model\Process\Import\Product\Update\Gallery $gallery,
        \MageOS\NetSuiteConnector\ProductImages\Model\Process\Import\Product\Update\Downloader $downloader,
        \MageOS\NetSuiteConnector\ProductImages\Model\Process\Import\Product\Update\Filesystem $filesystem
    ) {
        $this->imageConfig = $imageConfig;
        $this->productRepository = $productRepository;
        $this->gallery = $gallery;
        $this->downloader = $downloader;
        $this->filesystem = $filesystem;
    }

    /**
     * Load product images from NS for given NS product and them to magento product
     *
     * @param DataObject $product
     * @param Record $netSuiteItem
     * @return void
     * @throws \Exception
     */
    public function process(DataObject $product, Record $netSuiteItem): void
    {
        $nsImagesData = $this->extractImagesData($netSuiteItem);
        $existingProduct = $this->getExistingProduct($product->getData('sku'));
        if ($existingProduct) {
            $this->gallery->cleanUpMediaGallery($existingProduct, $nsImagesData);
            // skip already imported images
            foreach ($nsImagesData as $key => $nsImageData) {
                if ($this->gallery->isImageImported($existingProduct, $nsImageData['name'])) {
                    unset($nsImagesData[$key]);
                }
            }
        }
        $nsImagesToImport = $this->downloader->downloadImages($nsImagesData);
        $this->updateProduct($product, $nsImagesToImport);
    }

    /**
     * Load existing product by sku
     *
     * @param string $sku
     * @return ProductInterface
     */
    private function getExistingProduct($sku)
    {
        $existingProduct = null;
        try {
            $existingProduct = $this->productRepository->get($sku);
            // phpcs:disable
        } catch (NoSuchEntityException $e) {
            //do nothing - new product import
        }
        return $existingProduct;
    }

    /**
     * Get images data from custom fields of given NS product
     *
     * @param Record $netSuiteItem
     * @return array
     */
    private function extractImagesData(Record $netSuiteItem): array
    {
        $imagesData = [];
        $customFieldsIds = array_filter(explode(',', $this->imageConfig->getImageAttributeNsIds()));
        foreach ($customFieldsIds as $customFieldId) {
            $value = CustomFieldAccess::get($netSuiteItem, $customFieldId);
            if (!$value) {
                continue;
            }
            $imagesData[$customFieldId] = [
                'name' => $this->filesystem->cleanUpFilename($value->name),
                'internalId' => $value->internalId
            ];
        }

        return $imagesData;
    }

    /**
     * Add information about imported images to product
     *
     * @param DataObject $product
     * @param array $nsImagesToImport
     * @return void
     */
    private function updateProduct($product, $nsImagesToImport): void
    {
        if (empty($nsImagesToImport)) {
            return;
        }
        $baseImageId = $this->imageConfig->getBaseImageAttributeNsId();
        if (!empty($baseImageId) && !empty($nsImagesToImport[$baseImageId])) {
            $product->setData('base_image', $nsImagesToImport[$baseImageId]);
            $product->setData('base_image_label', 'Label');
            $product->setData('small_image', $nsImagesToImport[$baseImageId]);
            $product->setData('small_image_label', 'Label');
            $product->setData('thumbnail', $nsImagesToImport[$baseImageId]);
            $product->setData('thumbnail_image_label', 'Label');
            unset($nsImagesToImport[$baseImageId]);
        }
        if (count($nsImagesToImport) > 0) {
            $product->setData('additional_images', implode(',', $nsImagesToImport));
        }
    }
}
