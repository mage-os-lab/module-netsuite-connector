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
 *
 */
declare(strict_types=1);

namespace MageOS\NetSuiteConnector\Product\Model\Config;

use MageOS\NetSuiteConnector\Core\Model\Config\AbstractConfig;

/**
 * @method bool getPushLineItemsWithBundles
 * @method string getDefaultStoreIds
 * @method string getDefaultAttributeSetId
 * @method string getDefaultTaxClassId
 * @method string getDefaultVisibility
 * @method string getDefaultStatus
 * @method string getDefaultWebsiteIds
 * @method string getPriceLevelNetsuiteId
 * @method array getFieldMap
 * @method string getRelatedProductsField
 * @method string getUpsellsField
 * @method string getTierPriceCustomerGroup
 * @method array getHtmlTags
 * @method int getImportSpecialPrice
 * @method int getSpecialPricePriceLevel
 */
class ProductConfig extends AbstractConfig
{
    private const PUSH_LINE_ITEMS_WITH_BUNDLES = 'mageos_netsuite/products/push_line_items_with_bundles';

    private const DEFAULT_STORE_IDS = 'mageos_netsuite/products/default_store_ids';

    private const DEFAULT_ATTRIBUTE_SET_ID = 'mageos_netsuite/products/default_attribute_set_id';

    private const DEFAULT_VISIBILITY = 'mageos_netsuite/products/default_visibility';

    private const DEFAULT_STATUS = 'mageos_netsuite/products/default_status';

    private const DEFAULT_WEBSITE_IDS = 'mageos_netsuite/products/default_website_ids';

    private const PRICE_LEVEL_NETSUITE_ID = 'mageos_netsuite/products/price_level_netsuite_id';

    private const FIELD_MAP = 'mageos_netsuite/products/field_map';

    private const RELATED_PRODUCTS_FIELD = 'mageos_netsuite/products/related_products_field';

    private const UPSELLS_FIELD = 'mageos_netsuite/products/upsells_field';

    private const TIER_PRICE_CUSTOMER_GROUP = 'mageos_netsuite/products/tier_price_customer_group';

    private const HTML_TAGS = 'mageos_netsuite/products/html_tags';

    private const PRICE_LEVEL_MAP = 'mageos_netsuite/products/price_level_map';

    private const DEFAULT_TAX_LEVEL_ID = 'mageos_netsuite/products/default_tax_class_id';

    private const IMPORT_SPECIAL_PRICE = 'mageos_netsuite/products/import_special_price';

    private const SPECIAL_PRICE_PRICE_LEVEL = 'mageos_netsuite/products/special_price_price_level';

    /**
     * @return array
     */
    public function getOptionsMap(): array
    {
        return [
            self::PUSH_LINE_ITEMS_WITH_BUNDLES => 'bool',
            self::DEFAULT_STORE_IDS => 'string',
            self::DEFAULT_ATTRIBUTE_SET_ID => 'string',
            self::DEFAULT_VISIBILITY => 'string',
            self::DEFAULT_STATUS => 'string',
            self::DEFAULT_WEBSITE_IDS => 'string',
            self::PRICE_LEVEL_NETSUITE_ID => 'string',
            self::FIELD_MAP => 'json',
            self::RELATED_PRODUCTS_FIELD => 'string',
            self::UPSELLS_FIELD => 'string',
            self::TIER_PRICE_CUSTOMER_GROUP => 'string',
            self::HTML_TAGS => 'json',
            self::PRICE_LEVEL_MAP => 'json',
            self::DEFAULT_TAX_LEVEL_ID => 'int',
            self::IMPORT_SPECIAL_PRICE => 'int',
            self::SPECIAL_PRICE_PRICE_LEVEL => 'int',
        ];
    }
}
