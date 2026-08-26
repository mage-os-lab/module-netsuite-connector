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

namespace MageOS\NetSuiteConnector\ProductImages\Model\Config;

use MageOS\NetSuiteConnector\Core\Model\Config\AbstractConfig;

/**
 * This class provides access to product image import configuration
 *
 * @method string getBaseImageAttributeNsId
 * @method string getImageAttributeNsIds
 * @method bool getImportFilesBasedOnFilename
 */
class ImageConfig extends AbstractConfig
{
    private const BASE_IMAGE_ATTRIBUTE_NS_ID = 'mageos_netsuite/products/base_image_attribute_ns_id';

    private const IMAGE_ATTRIBUTE_NS_IDS = 'mageos_netsuite/products/image_attribute_ns_ids';

    private const IMPORT_FILES_BASED_ON_FILENAME = 'mageos_netsuite/products/import_files_based_on_filename';

    public function getOptionsMap(): array
    {
        return [
            self::BASE_IMAGE_ATTRIBUTE_NS_ID => 'string',
            self::IMAGE_ATTRIBUTE_NS_IDS => 'string',
            self::IMPORT_FILES_BASED_ON_FILENAME => 'bool',
        ];
    }
}
