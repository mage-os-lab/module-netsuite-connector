<?php
declare(strict_types=1);

namespace MageOS\NetSuiteConnector\Core\Model\NetSuite;

class NetsuiteCountries
{
    /**
     * @param string $netsuiteCountry
     * @param int $type
     * @return string
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function netsuiteCountryCodeToRegularCountryCode($netsuiteCountry, $type = 1): string// phpcs:ignore
    {
        $countries = [
            "_afghanistan" => [
                "AF",
                'Afghanistan'
            ],
            "_alandIslands" => [
                "AX",
                'Åland Islands'
            ],
            "_albania" => [
                "AL",
                'Albania'
            ],
            "_algeria" => [
                "DZ",
                'Algeria'
            ],
            "_americanSamoa" => [
                "AS",
                'American Samoa'
            ],
            "_andorra" => [
                "AD",
                'Andorra'
            ],
            "_angola" => [
                "AO",
                'Angola'
            ],
            "_anguilla" => [
                "AI",
                'Anguilla'
            ],
            "_antarctica" => [
                "AQ",
                'Antarctica'
            ],
            "_antiguaAndBarbuda" => [
                "AG",
                'Antigua & Barbuda'
            ],
            "_argentina" => [
                "AR",
                'Argentina'
            ],
            "_armenia" => [
                "AM",
                'Armenia'
            ],
            "_aruba" => [
                "AW",
                'Aruba'
            ],
            "_australia" => [
                "AU",
                'Australia'
            ],
            "_austria" => [
                "AT",
                'Austria'
            ],
            "_azerbaijan" => [
                "AZ",
                'Azerbaijan'
            ],
            "_bahamas" => [
                "BS",
                'Bahamas'
            ],
            "_bahrain" => [
                "BH",
                'Bahrain'
            ],
            "_bangladesh" => [
                "BD",
                'Bangladesh'
            ],
            "_barbados" => [
                "BB",
                'Barbados'
            ],
            "_belarus" => [
                "BY",
                'Belarus'
            ],
            "_belgium" => [
                "BE",
                'Belgium'
            ],
            "_belize" => [
                "BZ",
                'Belize'
            ],
            "_benin" => [
                "BJ",
                'Benin'
            ],
            "_bermuda" => [
                "BM",
                'Bermuda'
            ],
            "_bhutan" => [
                "BT",
                'Bhutan'
            ],
            "_bolivia" => [
                "BO",
                'Bolivia'
            ],
            "_bonaireSaintEustatiusAndSaba" => [
                "",
                ''
            ],
            "_bosniaAndHerzegovina" => [
                "BA",
                'Bosnia & Herzegovina'
            ],
            "_botswana" => [
                "BW",
                'Botswana'
            ],
            "_bouvetIsland" => [
                "BV",
                'Bouvet Island'
            ],
            "_brazil" => [
                "BR",
                'Brazil'
            ],
            "_britishIndianOceanTerritory" => [
                "IO",
                'British Indian Ocean Territory'
            ],
            "_bruneiDarussalam" => [
                "BN",
                'Brunei'
            ],
            "_bulgaria" => [
                "BG",
                'Bulgaria'
            ],
            "_burkinaFaso" => [
                "BF",
                'Burkina Faso'
            ],
            "_burundi" => [
                "BI",
                'Burundi'
            ],
            "_cambodia" => [
                "KH",
                'Cambodia'
            ],
            "_cameroon" => [
                "CM",
                'Cameroon'
            ],
            "_canada" => [
                "CA",
                'Canada'
            ],
            "_canaryIslands" => [
                "",
                ''
            ],
            "_capeVerde" => [
                "CV",
                'Cape Verde'
            ],
            "_caymanIslands" => [
                "KY",
                'Cayman Islands'
            ],
            "_centralAfricanRepublic" => [
                "CF",
                'Central African Republic'
            ],
            "_ceutaAndMelilla" => [
                "",
                ''
            ],
            "_chad" => [
                "TD",
                'Chad'
            ],
            "_chile" => [
                "CL",
                'Chile'
            ],
            "_china" => [
                "CN",
                'China'
            ],
            "_christmasIsland" => [
                "CX",
                'Christmas Island'
            ],
            "_cocosKeelingIslands" => [
                "CC",
                'Cocos (Keeling) Islands'
            ],
            "_colombia" => [
                "CO",
                'Colombia'
            ],
            "_comoros" => [
                "KM",
                'Comoros'
            ],
            "_congoDemocraticPeoplesRepublic" => [
                "CD",
                'Congo - Kinshasa'
            ],
            "_congoRepublicOf" => [
                "CG",
                'Congo - Brazzaville'
            ],
            "_cookIslands" => [
                "CK",
                'Cook Islands'
            ],
            "_costaRica" => [
                "CR",
                'Costa Rica'
            ],
            "_coteDIvoire" => [
                "CI",
                'Côte d’Ivoire'
            ],
            "_croatiaHrvatska" => [
                "HR",
                'Croatia'
            ],
            "_cuba" => [
                "CU",
                'Cuba'
            ],
            "_curacao" => [
                "",
                ''
            ],
            "_cyprus" => [
                "CY",
                'Cyprus'
            ],
            "_czechRepublic" => [
                "CZ",
                'Czech Republic'
            ],
            "_denmark" => [
                "DK",
                'Denmark'
            ],
            "_djibouti" => [
                "DJ",
                'Djibouti'
            ],
            "_dominica" => [
                "DM",
                'Dominica'
            ],
            "_dominicanRepublic" => [
                "DO",
                'Dominican Republic'
            ],
            "_eastTimor" => [
                "",
                'Timor-Leste'
            ],
            "_ecuador" => [
                "EC",
                'Ecuador'
            ],
            "_egypt" => [
                "EG",
                'Egypt'
            ],
            "_elSalvador" => [
                "SV",
                'El Salvador'
            ],
            "_equatorialGuinea" => [
                "GQ",
                'Equatorial Guinea'
            ],
            "_eritrea" => [
                "GQ",
                'Eritrea'
            ],
            "_estonia" => [
                "EE",
                'Estonia'
            ],
            "_ethiopia" => [
                "ET",
                'Ethiopia'
            ],
            "_falklandIslands" => [
                "FK",
                'Falkland Islands'
            ],
            "_faroeIslands" => [
                "FO",
                'Faroe Islands'
            ],
            "_fiji" => [
                "FJ",
                'Fiji'
            ],
            "_finland" => [
                "FI",
                'Finland'
            ],
            "_france" => [
                "FR",
                'France'
            ],
            "_frenchGuiana" => [
                "GF",
                'French Guiana'
            ],
            "_frenchPolynesia" => [
                "PF",
                'French Polynesia'
            ],
            "_frenchSouthernTerritories" => [
                "TF",
                'French Southern Territories'
            ],
            "_gabon" => [
                "GA",
                'Gabon'
            ],
            "_gambia" => [
                "GM",
                'Gambia'
            ],
            "_georgia" => [
                "GE",
                'Georgia'
            ],
            "_germany" => [
                "DE",
                'Germany'
            ],
            "_ghana" => [
                "GH",
                'Ghana'
            ],
            "_gibraltar" => [
                "GI",
                'Gibraltar'
            ],
            "_greece" => [
                "GR",
                'Greece'
            ],
            "_greenland" => [
                "GL",
                'Greenland'
            ],
            "_grenada" => [
                "GD",
                'Grenada'
            ],
            "_guadeloupe" => [
                "GP",
                'Guadeloupe'
            ],
            "_guam" => [
                "GU",
                'Guam'
            ],
            "_guatemala" => [
                "GT",
                'Guatemala'
            ],
            "_guernsey" => [
                "GG",
                'Guernsey'
            ],
            "_guinea" => [
                "GN",
                'Guinea'
            ],
            "_guineaBissau" => [
                "GW",
                'Guinea-Bissau'
            ],
            "_guyana" => [
                "GY",
                'Guyana'
            ],
            "_haiti" => [
                "HT",
                'Haiti'
            ],
            "_heardAndMcDonaldIslands" => [
                "HM",
                'Heard & McDonald Islands'
            ],
            "_holySeeCityVaticanState" => [
                "VA",
                'Vatican City'
            ],
            "_honduras" => [
                "HN",
                'Honduras'
            ],
            "_hongKong" => [
                "HK",
                'Hong Kong SAR China'
            ],
            "_hungary" => [
                "HU",
                'Hungary'
            ],
            "_iceland" => [
                "IS",
                'Iceland'
            ],
            "_india" => [
                "IN",
                'India'
            ],
            "_indonesia" => [
                "ID",
                'Indonesia'
            ],
            "_iranIslamicRepublicOf" => [
                "IR",
                'Iran'
            ],
            "_iraq" => [
                "IQ",
                'Iraq'
            ],
            "_ireland" => [
                "IE",
                'Ireland'
            ],
            "_isleOfMan" => [
                "IM",
                'Isle of Man'
            ],
            "_israel" => [
                "IL",
                'Israel'
            ],
            "_italy" => [
                "IT",
                'Italy'
            ],
            "_jamaica" => [
                "JM",
                'Jamaica'
            ],
            "_japan" => [
                "JP",
                'Japan'
            ],
            "_jersey" => [
                "JE",
                'Jersey'
            ],
            "_jordan" => [
                "JO",
                'Jordan'
            ],
            "_kazakhstan" => [
                "KZ",
                'Kazakhstan'
            ],
            "_kenya" => [
                "KE",
                'Kenya'
            ],
            "_kiribati" => [
                "KI",
                'Kiribati'
            ],
            "_koreaDemocraticPeoplesRepublic" => [
                "KP",
                'North Korea'
            ],
            "_koreaRepublicOf" => [
                "KR",
                'South Korea'
            ],
            "_kosovo" => [
                "",
                ''
            ],
            "_kuwait" => [
                "KW",
                'Kuwait'
            ],
            "_kyrgyzstan" => [
                "KG",
                'Kyrgyzstan'
            ],
            "_laoPeoplesDemocraticRepublic" => [
                "LA",
                'Laos'
            ],
            "_latvia" => [
                "LV",
                'Latvia'
            ],
            "_lebanon" => [
                "LB",
                'Lebanon'
            ],
            "_lesotho" => [
                "LS",
                'Lesotho'
            ],
            "_liberia" => [
                "LR",
                'Liberia'
            ],
            "_libya" => [
                "LY",
                'Libya'
            ],
            "_liechtenstein" => [
                "LI",
                'Liechtenstein'
            ],
            "_lithuania" => [
                "LT",
                'Lithuania'
            ],
            "_luxembourg" => [
                "LU",
                'Luxembourg'
            ],
            "_macau" => [
                "",
                'Macau SAR China'
            ],
            "_macedonia" => [
                "MK",
                'Macedonia'
            ],
            "_madagascar" => [
                "MG",
                'Madagascar'
            ],
            "_malawi" => [
                "MW",
                'Malawi'
            ],
            "_malaysia" => [
                "MY",
                'Malaysia'
            ],
            "_maldives" => [
                "MV",
                'Maldives'
            ],
            "_mali" => [
                "ML",
                'Mali'
            ],
            "_malta" => [
                "MT",
                'Malta'
            ],
            "_marshallIslands" => [
                "MH",
                'Marshall Islands'
            ],
            "_martinique" => [
                "MQ",
                'Martinique'
            ],
            "_mauritania" => [
                "MR",
                'Mauritania'
            ],
            "_mauritius" => [
                "MU",
                'Mauritius'
            ],
            "_mayotte" => [
                "YT",
                'Mayotte'
            ],
            "_mexico" => [
                "MX",
                'Mexico'
            ],
            "_micronesiaFederalStateOf" => [
                "FM",
                'Micronesia'
            ],
            "_moldovaRepublicOf" => [
                "MD",
                'Moldova'
            ],
            "_monaco" => [
                "MC",
                'Monaco'
            ],
            "_mongolia" => [
                "MN",
                'Mongolia'
            ],
            "_montenegro" => [
                "ME",
                'Montenegro'
            ],
            "_montserrat" => [
                "MS",
                'Montserrat'
            ],
            "_morocco" => [
                "MA",
                'Morocco'
            ],
            "_mozambique" => [
                "MZ",
                'Mozambique'
            ],
            "_myanmar" => [
                "MM",
                'Myanmar (Burma)'
            ],
            "_namibia" => [
                "NA",
                'Namibia'
            ],
            "_nauru" => [
                "NR",
                'Nauru'
            ],
            "_nepal" => [
                "NP",
                'Nepal'
            ],
            "_netherlands" => [
                "NL",
                'Netherlands'
            ],
            "_newCaledonia" => [
                "NC",
                'New Caledonia'
            ],
            "_newZealand" => [
                "NZ",
                'New Zealand'
            ],
            "_nicaragua" => [
                "NI",
                'Nicaragua'
            ],
            "_niger" => [
                "NE",
                'Niger'
            ],
            "_nigeria" => [
                "NG",
                'Nigeria'
            ],
            "_niue" => [
                "NU",
                'Niue'
            ],
            "_norfolkIsland" => [
                "NF",
                'Norfolk Island'
            ],
            "_northernMarianaIslands" => [
                "MP",
                'Northern Mariana Islands'
            ],
            "_norway" => [
                "NO",
                'Norway'
            ],
            "_oman" => [
                "OM",
                'Oman'
            ],
            "_pakistan" => [
                "PK",
                'Pakistan'
            ],
            "_palau" => [
                "PW",
                'Palau'
            ],
            "_palestinianTerritories" => [
                "PS",
                'Palestinian Territories'
            ],
            "_panama" => [
                "PA",
                'Panama'
            ],
            "_papuaNewGuinea" => [
                "PG",
                'Papua New Guinea'
            ],
            "_paraguay" => [
                "PY",
                'Paraguay'
            ],
            "_peru" => [
                "PE",
                'Peru'
            ],
            "_philippines" => [
                "PH",
                'Philippines'
            ],
            "_pitcairnIsland" => [
                "PN",
                'Pitcairn Islands'
            ],
            "_poland" => [
                "PL",
                'Poland'
            ],
            "_portugal" => [
                "PT",
                'Portugal'
            ],
            "_puertoRico" => [
                "PR",
                ''
            ],
            "_qatar" => [
                "QA",
                'Qatar'
            ],
            "_reunionIsland" => [
                "RE",
                'Réunion'
            ],
            "_romania" => [
                "RO",
                'Romania'
            ],
            "_russianFederation" => [
                "RU",
                'Russia'
            ],
            "_rwanda" => [
                "RW",
                'Rwanda'
            ],
            "_saintBarthelemy" => [
                "BL",
                'St. Barthélemy'
            ],
            "_saintHelena" => [
                "SH",
                'St. Helena'
            ],
            "_saintKittsAndNevis" => [
                "KN",
                'St. Kitts & Nevis'
            ],
            "_saintLucia" => [
                "LC",
                'St. Lucia'
            ],
            "_saintMartin" => [
                "MF",
                'St. Martin'
            ],
            "_saintVincentAndTheGrenadines" => [
                "VC",
                'St. Vincent & Grenadines'
            ],
            "_samoa" => [
                "WS",
                'Samoa'
            ],
            "_sanMarino" => [
                "SM",
                'San Marino'
            ],
            "_saoTomeAndPrincipe" => [
                "ST",
                'São Tomé & Príncipe'
            ],
            "_saudiArabia" => [
                "SA",
                'Saudi Arabia'
            ],
            "_senegal" => [
                "SN",
                'Senegal'
            ],
            "_serbia" => [
                "RS",
                'Serbia'
            ],
            "_seychelles" => [
                "SC",
                'Seychelles'
            ],
            "_sierraLeone" => [
                "SL",
                'Sierra Leone'
            ],
            "_singapore" => [
                "SG",
                'Singapore'
            ],
            "_sintMaarten" => [
                "",
                ''
            ],
            "_slovakRepublic" => [
                "SK",
                'Slovakia'
            ],
            "_slovenia" => [
                "SI",
                'Slovenia'
            ],
            "_solomonIslands" => [
                "SB",
                'Solomon Islands'
            ],
            "_somalia" => [
                "SO",
                'Somalia'
            ],
            "_southAfrica" => [
                "ZA",
                'South Africa'
            ],
            "_southGeorgia" => [
                "GS",
                'South Georgia & South Sandwich Islands'
            ],
            "_southSudan" => [
                "SS",
                ''
            ],
            "_spain" => [
                "ES",
                'Spain'
            ],
            "_sriLanka" => [
                "LK",
                'Sri Lanka'
            ],
            "_stPierreAndMiquelon" => [
                "PM",
                'St. Pierre & Miquelon'
            ],
            "_sudan" => [
                "SD",
                'Sudan'
            ],
            "_suriname" => [
                "SR",
                'Suriname'
            ],
            "_svalbardAndJanMayenIslands" => [
                "SJ",
                'Svalbard & Jan Mayen'
            ],
            "_swaziland" => [
                "SZ",
                'Swaziland'
            ],
            "_sweden" => [
                "SE",
                'Sweden'
            ],
            "_switzerland" => [
                "CH",
                'Switzerland'
            ],
            "_syrianArabRepublic" => [
                "SY",
                'Syria'
            ],
            "_taiwan" => [
                "TW",
                'Taiwan'
            ],
            "_tajikistan" => [
                "TJ",
                'Tajikistan'
            ],
            "_tanzania" => [
                "TZ",
                'Tanzania'
            ],
            "_thailand" => [
                "TH",
                'Thailand'
            ],
            "_togo" => [
                "TG",
                'Togo'
            ],
            "_tokelau" => [
                "TK",
                'Tokelau'
            ],
            "_tonga" => [
                "TO",
                'Tonga'
            ],
            "_trinidadAndTobago" => [
                "TT",
                'Trinidad & Tobago'
            ],
            "_tunisia" => [
                "TN",
                'Tunisia'
            ],
            "_turkey" => [
                "TR",
                'Turkey'
            ],
            "_turkmenistan" => [
                "TM",
                'Turkmenistan'
            ],
            "_turksAndCaicosIslands" => [
                "TC",
                'Turks & Caicos Islands'
            ],
            "_tuvalu" => [
                "TV",
                'Tuvalu'
            ],
            "_uganda" => [
                "UG",
                'Uganda'
            ],
            "_ukraine" => [
                "UA",
                'Ukraine'
            ],
            "_unitedArabEmirates" => [
                "AE",
                'United Arab Emirates'
            ],
            "_unitedKingdom" => [
                "GB",
                'United Kingdom'
            ],
            "_unitedStates" => [
                "US",
                'United States'
            ],
            "_uruguay" => [
                "UY",
                'Uruguay'
            ],
            "_uSMinorOutlyingIslands" => [
                "UM",
                'U.S. Outlying Islands'
            ],
            "_uzbekistan" => [
                "UZ",
                'Uzbekistan'
            ],
            "_vanuatu" => [
                "VU",
                'Vanuatu'
            ],
            "_venezuela" => [
                "VE",
                'Venezuela'
            ],
            "_vietnam" => [
                "VN",
                'Vietnam'
            ],
            "_virginIslandsBritish" => [
                "VG",
                'British Virgin Islands'
            ],
            "_virginIslandsUSA" => [
                "VI",
                'U.S. Virgin Islands'
            ],
            "_wallisAndFutunaIslands" => [
                "WF",
                'Wallis & Futuna'
            ],
            "_westernSahara" => [
                "EH",
                'Western Sahara'
            ],
            "_yemen" => [
                "YE",
                'Yemen'
            ],
            "_zambia" => [
                "ZM",
                'Zambia'
            ],
            "_zimbabwe" => [
                "ZW",
                'Zimbabwe'
            ],
        ];

        return $countries[$netsuiteCountry][$type];
    }
}
