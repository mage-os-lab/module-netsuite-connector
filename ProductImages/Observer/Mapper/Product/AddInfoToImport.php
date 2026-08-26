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

namespace MageOS\NetSuiteConnector\ProductImages\Observer\Mapper\Product;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use \MageOS\NetSuiteConnector\ProductImages\Model\ConfigProvider\Permissions;

/**
 * This observer run image import for product. It will download product images from NS for specific NS product and
 * them to magento product.
 */
class AddInfoToImport implements ObserverInterface
{
    /**
     * @var \MageOS\NetSuiteConnector\ProductImages\Model\ConfigProvider\Permissions
     */
    private $permission;

    /**
     * @var \MageOS\NetSuiteConnector\ProductImages\Model\Process\Import\Product\Update\Images
     */
    private $imagesProcessor;

    /**
     * @param \MageOS\NetSuiteConnector\ProductImages\Model\ConfigProvider\Permissions $permission
     * @param \MageOS\NetSuiteConnector\ProductImages\Model\Process\Import\Product\Update\Images $imagesProcessor
     */
    public function __construct(
        \MageOS\NetSuiteConnector\ProductImages\Model\ConfigProvider\Permissions $permission,
        \MageOS\NetSuiteConnector\ProductImages\Model\Process\Import\Product\Update\Images $imagesProcessor
    ) {
        $this->permission = $permission;
        $this->imagesProcessor = $imagesProcessor;
    }

    /**
     * Run image import for product
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer): ObserverInterface
    {
        if (!$this->permission->isFeatureEnabled(Permissions::GET_PRODUCT_IMAGES)) {
            return $this;
        }
        $this->imagesProcessor->process($observer->getData('magento_product'), $observer->getData('netsuite_product'));
        return $this;
    }
}
