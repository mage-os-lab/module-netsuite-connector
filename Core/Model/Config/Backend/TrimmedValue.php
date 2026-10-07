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
 * Saves a connection field without surrounding whitespace
 *
 * Host, account id, consumer key and token id are pasted from NetSuite. A stray space breaks the
 * WSDL URL ("Couldn't load from 'https://… /wsdl/…'") or the token signature ("Invalid login
 * attempt."), and neither error points at the space.
 */
class TrimmedValue extends \Magento\Framework\App\Config\Value
{
    /**
     * Trim the submitted value before it is saved
     *
     * @return $this
     */
    public function beforeSave()
    {
        $this->setValue(trim((string)$this->getValue()));

        return parent::beforeSave();
    }
}
