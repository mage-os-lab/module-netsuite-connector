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
 */

namespace MageOS\NetSuiteConnector\Core\Model\Config\Backend;

/**
 * Encrypts a secret without surrounding whitespace, see TrimmedValue
 *
 * The trim runs before Encrypted::beforeSave(), so the obscured value the admin form sends back for
 * an unchanged secret is still recognised and left alone.
 */
class TrimmedEncrypted extends \Magento\Config\Model\Config\Backend\Encrypted
{
    /**
     * Trim the submitted secret before it is encrypted
     *
     * @return void
     */
    public function beforeSave()
    {
        $this->setValue(trim((string)$this->getValue()));

        parent::beforeSave();
    }
}
