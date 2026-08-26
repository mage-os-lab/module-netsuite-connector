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

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * This class contains a set of methods related to files
 */
class Filesystem
{
    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    private $mediaDirectory;

    /**
     * @param \Magento\Framework\Filesystem $filesystem
     */
    public function __construct(
        \Magento\Framework\Filesystem $filesystem
    ) {
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    /**
     * Create file inside media import directory
     *
     * @param string $filename
     * @param string $content
     */
    public function addImageToImportDir($filename, $content)
    {
        $this->mediaDirectory->writeFile(
            'import' . DIRECTORY_SEPARATOR . $filename,
            $content
        );
    }

    /**
     * Remove image file from media directory
     *
     * @param string $filename
     */
    public function deleteImageFromMediaGallery($filename)
    {
        $this->mediaDirectory->delete($filename);
    }

    /**
     * Remove all unwanted symbols from filename
     *
     * @param string $name
     * @return string
     */
    public function cleanUpFilename(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_.]/', '_', $name);
    }
}
