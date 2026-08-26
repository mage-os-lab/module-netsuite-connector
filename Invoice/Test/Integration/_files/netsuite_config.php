<?php

use Magento\Framework\App\Config\ScopeConfigInterface;

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$fieldMap =
    [
        '_1483610974286_286' =>
            [
                'netsuite' => 'custitem_magento_product_name',
                'netsuite_settings' => 'custom_simple',
                'netsuite_list_id' => '',
                'netsuite_field_value' => '',
                'magento' => 'name',
            ],
        '_1483611362216_216' =>
            [
                'netsuite' => 'itemId',
                'netsuite_settings' => 'standard_field',
                'netsuite_list_id' => '',
                'netsuite_field_value' => '',
                'magento' => 'sku',
            ],
        '_1483612045168_168' =>
            [
                'netsuite' => 'custitem1',
                'netsuite_settings' => 'custom_list',
                'netsuite_list_id' => '16',
                'netsuite_field_value' => '',
                'magento' => 'test_super_attr',
            ],
        '_1483612081201_201' =>
            [
                'netsuite' => 'custitem2',
                'netsuite_settings' => 'custom_list',
                'netsuite_list_id' => '12',
                'netsuite_field_value' => '',
                'magento' => 'lens_power',
            ],
        '_1483612142333_333' =>
            [
                'netsuite' => 'custitem3',
                'netsuite_settings' => 'custom_checkbox',
                'netsuite_list_id' => '',
                'netsuite_field_value' => '',
                'magento' => 'backorderable',
            ],
        '_1484226323191_191' =>
            [
                'netsuite' => 'custitem16',
                'netsuite_settings' => 'custom_list',
                'netsuite_list_id' => '40',
                'netsuite_field_value' => '',
                'magento' => 'launch_season',
            ],
        '_1484227146159_159' =>
            [
                'netsuite' => 'salesDescription',
                'netsuite_settings' => 'standard_field',
                'netsuite_list_id' => '',
                'netsuite_field_value' => '',
                'magento' => 'sales_description',
            ],
        '_1484227160368_368' =>
            [
                'netsuite' => 'storeDescription',
                'netsuite_settings' => 'standard_field',
                'netsuite_list_id' => '',
                'netsuite_field_value' => '',
                'magento' => 'description',
            ],
        '_1484227683979_979' =>
            [
                'netsuite' => 'custitem4',
                'netsuite_settings' => 'custom_checkbox',
                'netsuite_list_id' => '',
                'netsuite_field_value' => '',
                'magento' => 'makeable_product',
            ],
        '_1484227719988_988' =>
            [
                'netsuite' => 'custitem5',
                'netsuite_settings' => 'custom_list',
                'netsuite_list_id' => '32',
                'netsuite_field_value' => '',
                'magento' => 'front_color',
            ],
        '_1484227764505_505' =>
            [
                'netsuite' => 'custitem7',
                'netsuite_settings' => 'custom_list',
                'netsuite_list_id' => '33',
                'netsuite_field_value' => '',
                'magento' => 'finish_front',
            ],
        '_1484227840176_176' =>
            [
                'netsuite' => 'custitem8',
                'netsuite_settings' => 'custom_list',
                'netsuite_list_id' => '34',
                'netsuite_field_value' => '',
                'magento' => 'temple_color',
            ],
        '_1484228023496_496' =>
            [
                'netsuite' => 'custitem10',
                'netsuite_settings' => 'custom_list',
                'netsuite_list_id' => '35',
                'netsuite_field_value' => '',
                'magento' => 'finish_temple',
            ],
        '_1484228129745_745' =>
            [
                'netsuite' => 'custitem11',
                'netsuite_settings' => 'custom_list',
                'netsuite_list_id' => '36',
                'netsuite_field_value' => '',
                'magento' => 'treatment',
            ],
        '_1484228259062_62' =>
            [
                'netsuite' => 'custitem13',
                'netsuite_settings' => 'custom_list',
                'netsuite_list_id' => '14',
                'netsuite_field_value' => '',
                'magento' => 'gender',
            ],
        '_1484228290430_430' =>
            [
                'netsuite' => 'custitem14',
                'netsuite_settings' => 'custom_list',
                'netsuite_list_id' => '37',
                'netsuite_field_value' => '',
                'magento' => 'rimless',
            ],
        '_1484228319814_814' =>
            [
                'netsuite' => 'custitem15',
                'netsuite_settings' => 'custom_checkbox',
                'netsuite_list_id' => '',
                'netsuite_field_value' => '',
                'magento' => 'holiday',
            ],
        '_1484228350419_419' =>
            [
                'netsuite' => 'custitem12',
                'netsuite_settings' => 'custom_checkbox',
                'netsuite_list_id' => '',
                'netsuite_field_value' => '',
                'magento' => 'combination',
            ],
        '_1484228372490_490' =>
            [
                'netsuite' => 'custitem17',
                'netsuite_settings' => 'custom_checkbox',
                'netsuite_list_id' => '',
                'netsuite_field_value' => '',
                'magento' => 'limited_edition',
            ],
        '_1484228398324_324' =>
            [
                'netsuite' => 'custitem20',
                'netsuite_settings' => 'custom_simple',
                'netsuite_list_id' => '',
                'netsuite_field_value' => '',
                'magento' => 'frame_measurement',
            ],
        '_1484228442279_279' =>
            [
                'netsuite' => 'custitem25',
                'netsuite_settings' => 'custom_list',
                'netsuite_list_id' => '95',
                'netsuite_field_value' => '',
                'magento' => 'seasonal_product',
            ],
        '_1484228478503_503' =>
            [
                'netsuite' => 'custitem_frame_collection',
                'netsuite_settings' => 'custom_list',
                'netsuite_list_id' => '96',
                'netsuite_field_value' => '',
                'magento' => 'frame_collection',
            ],
        '_1484228531310_310' =>
            [
                'netsuite' => 'custitemframe_shape',
                'netsuite_settings' => 'custom_list',
                'netsuite_list_id' => '97',
                'netsuite_field_value' => '',
                'magento' => 'shape',
            ],
        '_1484228576733_733' =>
            [
                'netsuite' => 'custitemmaterial',
                'netsuite_settings' => 'custom_list',
                'netsuite_list_id' => '98',
                'netsuite_field_value' => '',
                'magento' => 'material',
            ],
        '_1484228609729_729' =>
            [
                'netsuite' => 'custitemsize',
                'netsuite_settings' => 'custom_list',
                'netsuite_list_id' => '99',
                'netsuite_field_value' => '',
                'magento' => 'size',
            ],
        '_1484228652936_936' =>
            [
                'netsuite' => 'custitemcolor_collection',
                'netsuite_settings' => 'custom_list',
                'netsuite_list_id' => '96',
                'netsuite_field_value' => '',
                'magento' => 'color_collection',
            ],
        '_1484228749294_294' =>
            [
                'netsuite' => 'custitem_temple_length',
                'netsuite_settings' => 'custom_simple',
                'netsuite_list_id' => '',
                'netsuite_field_value' => '',
                'magento' => 'temple_length',
            ],
        '_1484228758370_370' =>
            [
                'netsuite' => 'custitem_lens_depth',
                'netsuite_settings' => 'custom_simple',
                'netsuite_list_id' => '',
                'netsuite_field_value' => '',
                'magento' => 'lens_depth',
            ],
        '_1484228779556_556' =>
            [
                'netsuite' => 'custitem_lens_width',
                'netsuite_settings' => 'custom_simple',
                'netsuite_list_id' => '',
                'netsuite_field_value' => '',
                'magento' => 'lens_width',
            ],
        '_1484228799567_567' =>
            [
                'netsuite' => 'custitem_frame_width',
                'netsuite_settings' => 'custom_simple',
                'netsuite_list_id' => '',
                'netsuite_field_value' => '',
                'magento' => 'frame_width',
            ],
    ];


$shipping_map = [
    [
        'shipping_method' => 'test_method',
        'internal_netsuite_id' => '1',
    ]
];

$payment_map = [
    [
        'payment_method' => 'checkmo',
        'payment_cc' => '',
        'internal_netsuite_id' => '1',
    ]

];

$configMap = [
    [
        'mageos_netsuite/products/default_visibility',
        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        null,
        '4'
    ],
    [
        'mageos_netsuite/products/default_status',
        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        null,
        '1'
    ],
    [
        'mageos_netsuite/products/default_website_ids',
        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        null,
        '1'
    ],
    [
        'mageos_netsuite/products/field_map',
        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        null,
        json_encode($fieldMap)
    ],
    [
        'mageos_netsuite/products/price_level_netsuite_id',
        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        null,
        '5'
    ],
    [
        'mageos_netsuite/products/tier_price_customer_group',
        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        null,
        '32000'
    ],
    [
        'mageos_netsuite/stock/stock_stored_at_location_level',
        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        null,
        '0'
    ],
    [
        'mageos_netsuite/stock/qty_field_name',
        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        null,
        'custitem_quantity'
    ],
    [
        'mageos_netsuite/products/related_products_field',
        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        null,
        'custitem_related'
    ],
    [
        'mageos_netsuite/products/upsells_field',
        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        null,
        'custitem_upsells'
    ],
    [
        'mageos_netsuite/shipping_methods/netsuite_mapping',
        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        null,
        json_encode($shipping_map)
    ],
    [
        'mageos_netsuite/payment_methods/netsuite_mapping',
        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        null,
        json_encode($payment_map)
    ],
    [
        'mageos_netsuite/orders/status_map',
        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        null,
        json_encode([])
    ],
    [
        'mageos_netsuite/shipping_methods/netsuite_default_shipping_id',
        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        null,
        '1'
    ],
    [
        'cataloginventory/item_options/backorders',
        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        null,
        '1'
    ]
];

/** @var \Magento\Framework\App\Config\Storage\WriterInterface $configWriter */
$configWriter = $objectManager->get(\Magento\Framework\App\Config\Storage\WriterInterface::class);

foreach ($configMap as $entry) {
    $configWriter->save($entry[0], $entry[3], $entry[1]);
}

// clear cache
$scopeConfig = $objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
$scopeConfig->clean();

// Fill-in customer cache so we won't hit NS
$customerHelper = $objectManager->get(\MageOS\NetSuiteConnector\Customer\Model\Mapper\Customer::class);

$refObject = new \ReflectionObject($customerHelper);

$customer1 = new \NetSuite\Classes\Customer();
$customer1->email = 'joe.doe@rocketweb.com';
$customer1->firstName = 'Joe';
$customer1->lastName = 'Doe';
$customer1->middleName = 'J.';
$customer1->altName = 'Joe Doe';
$customer1->companyName = 'RocketWeb';
$customer1->fax = '12345678';

$refProperty = $refObject->getProperty('customerCache');
$refProperty->setValue($customerHelper, [
    '1' => $customer1,
]);

////////////////////////////
/// Populate NetSuite lists
///
$lists = [
    // colors
    16 =>
        [
            1 => '00',
            2 => '01',
            3 => '02',
            4 => '03',
            5 => '04',
            6 => '05',
            7 => '06',
            8 => '07',
            9 => '08',
            62 => '09',
            10 => '10',
            117 => '100',
            11 => '11',
            12 => '12',
            87 => '13',
            13 => '14',
            14 => '15',
            15 => '16',
            16 => '17',
            17 => '18',
            18 => '19',
            19 => '20',
            91 => '21',
            81 => '22',
            74 => '23',
            76 => '24',
            20 => '25',
            63 => '26',
            21 => '27',
            93 => '28',
            75 => '29',
            22 => '30',
            64 => '31',
            23 => '32',
            92 => '33',
            94 => '34',
            24 => '35',
            86 => '36',
            72 => '37',
            118 => '38',
            73 => '39',
            65 => '40',
            25 => '41',
            84 => '42',
            95 => '43',
            26 => '44',
            27 => '45',
            28 => '46',
            29 => '47',
            30 => '48',
            96 => '49',
            80 => '50',
            31 => '51',
            32 => '52',
            85 => '53',
            33 => '54',
            97 => '55',
            89 => '56',
            98 => '57',
            99 => '58',
            34 => '59',
            100 => '60',
            101 => '61',
            102 => '62',
            103 => '63',
            104 => '64',
            105 => '65',
            66 => '66',
            35 => '67',
            36 => '68',
            106 => '69',
            83 => '70',
            88 => '71',
            107 => '72',
            37 => '73',
            38 => '74',
            39 => '75',
            40 => '76',
            41 => '77',
            82 => '78',
            108 => '79',
            109 => '80',
            90 => '81',
            42 => '82',
            43 => '83',
            79 => '84',
            110 => '85',
            44 => '86',
            45 => '87',
            78 => '88',
            111 => '89',
            112 => '90',
            113 => '91',
            114 => '92',
            46 => '93',
            115 => '94',
            116 => '95',
            77 => '96',
            47 => '97',
            48 => '98',
            49 => '99',
            67 => 'BK',
            68 => 'BL',
            50 => 'F9',
            51 => 'H1',
            52 => 'H4',
            69 => 'H5',
            70 => 'PK',
            53 => 'T1',
            60 => 'T11',
            61 => 'T15',
            54 => 'T2',
            55 => 'T3',
            56 => 'T4',
            57 => 'T5',
            58 => 'T6',
            59 => 'T7',
            71 => 'TO',
        ],
];

/** @var \Magento\Framework\App\CacheInterface $helper */
$cache = $objectManager->get('Magento\Framework\App\CacheInterface');

foreach ($lists as $listId => $listData) {
    $key = 'custom_list_' . $listId;
    $cache->save(json_encode($listData), $key, [\Magento\Framework\App\Cache\Type\Config::CACHE_TAG], 3600);
}
