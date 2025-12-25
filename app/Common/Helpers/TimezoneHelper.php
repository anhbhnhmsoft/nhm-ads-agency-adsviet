<?php

namespace App\Common\Helpers;

use DateTimeZone;

class TimezoneHelper
{
    /**
     * Lấy danh sách timezone IDs cho Meta (Facebook)
     * Dữ liệu từ: https://developers.facebook.com/docs/marketing-api/reference/ad-account/timezone-ids/v24.0
     * 
     * @return array [id => name]
     */
    public static function getMetaTimezoneIds(): array
    {
        return [
            0 => 'TZ_UNKNOWN',
            1 => 'TZ_AMERICA_LOS_ANGELES',
            2 => 'TZ_AMERICA_DENVER',
            3 => 'TZ_PACIFIC_HONOLULU',
            4 => 'TZ_AMERICA_ANCHORAGE',
            5 => 'TZ_AMERICA_PHOENIX',
            6 => 'TZ_AMERICA_CHICAGO',
            7 => 'TZ_AMERICA_NEW_YORK',
            8 => 'TZ_ASIA_DUBAI',
            9 => 'TZ_AMERICA_ARGENTINA_SAN_LUIS',
            10 => 'TZ_AMERICA_ARGENTINA_BUENOS_AIRES',
            11 => 'TZ_AMERICA_ARGENTINA_SALTA',
            12 => 'TZ_EUROPE_VIENNA',
            13 => 'TZ_AUSTRALIA_PERTH',
            14 => 'TZ_AUSTRALIA_BROKEN_HILL',
            15 => 'TZ_AUSTRALIA_SYDNEY',
            16 => 'TZ_EUROPE_SARAJEVO',
            17 => 'TZ_ASIA_DHAKA',
            18 => 'TZ_EUROPE_BRUSSELS',
            19 => 'TZ_EUROPE_SOFIA',
            20 => 'TZ_ASIA_BAHRAIN',
            21 => 'TZ_AMERICA_LA_PAZ',
            22 => 'TZ_AMERICA_NORONHA',
            23 => 'TZ_AMERICA_CAMPO_GRANDE',
            24 => 'TZ_AMERICA_BELEM',
            25 => 'TZ_AMERICA_SAO_PAULO',
            26 => 'TZ_AMERICA_NASSAU',
            27 => 'TZ_AMERICA_DAWSON',
            28 => 'TZ_AMERICA_VANCOUVER',
            29 => 'TZ_AMERICA_DAWSON_CREEK',
            30 => 'TZ_AMERICA_EDMONTON',
            31 => 'TZ_AMERICA_RAINY_RIVER',
            32 => 'TZ_AMERICA_REGINA',
            33 => 'TZ_AMERICA_ATIKOKAN',
            34 => 'TZ_AMERICA_IQALUIT',
            35 => 'TZ_AMERICA_TORONTO',
            36 => 'TZ_AMERICA_BLANC_SABLON',
            37 => 'TZ_AMERICA_HALIFAX',
            38 => 'TZ_AMERICA_ST_JOHNS',
            39 => 'TZ_EUROPE_ZURICH',
            40 => 'TZ_PACIFIC_EASTER',
            41 => 'TZ_AMERICA_SANTIAGO',
            42 => 'TZ_ASIA_SHANGHAI',
            43 => 'TZ_AMERICA_BOGOTA',
            44 => 'TZ_AMERICA_COSTA_RICA',
            45 => 'TZ_ASIA_NICOSIA',
            46 => 'TZ_EUROPE_PRAGUE',
            47 => 'TZ_EUROPE_BERLIN',
            48 => 'TZ_EUROPE_COPENHAGEN',
            49 => 'TZ_AMERICA_SANTO_DOMINGO',
            50 => 'TZ_PACIFIC_GALAPAGOS',
            51 => 'TZ_AMERICA_GUAYAQUIL',
            52 => 'TZ_EUROPE_TALLINN',
            53 => 'TZ_AFRICA_CAIRO',
            54 => 'TZ_ATLANTIC_CANARY',
            55 => 'TZ_EUROPE_MADRID',
            56 => 'TZ_EUROPE_HELSINKI',
            57 => 'TZ_EUROPE_PARIS',
            58 => 'TZ_EUROPE_LONDON',
            59 => 'TZ_AFRICA_ACCRA',
            60 => 'TZ_EUROPE_ATHENS',
            61 => 'TZ_AMERICA_GUATEMALA',
            62 => 'TZ_ASIA_HONG_KONG',
            63 => 'TZ_AMERICA_TEGUCIGALPA',
            64 => 'TZ_EUROPE_ZAGREB',
            65 => 'TZ_EUROPE_BUDAPEST',
            66 => 'TZ_ASIA_JAKARTA',
            67 => 'TZ_ASIA_MAKASSAR',
            68 => 'TZ_ASIA_JAYAPURA',
            69 => 'TZ_EUROPE_DUBLIN',
            70 => 'TZ_ASIA_JERUSALEM',
            71 => 'TZ_ASIA_KOLKATA',
            72 => 'TZ_ASIA_BAGHDAD',
            73 => 'TZ_ATLANTIC_REYKJAVIK',
            74 => 'TZ_EUROPE_ROME',
            75 => 'TZ_AMERICA_JAMAICA',
            76 => 'TZ_ASIA_AMMAN',
            77 => 'TZ_ASIA_TOKYO',
            78 => 'TZ_AFRICA_NAIROBI',
            79 => 'TZ_ASIA_SEOUL',
            80 => 'TZ_ASIA_KUWAIT',
            81 => 'TZ_ASIA_BEIRUT',
            82 => 'TZ_ASIA_COLOMBO',
            83 => 'TZ_EUROPE_VILNIUS',
            84 => 'TZ_EUROPE_LUXEMBOURG',
            85 => 'TZ_EUROPE_RIGA',
            86 => 'TZ_AFRICA_CASABLANCA',
            87 => 'TZ_EUROPE_SKOPJE',
            88 => 'TZ_EUROPE_MALTA',
            89 => 'TZ_INDIAN_MAURITIUS',
            90 => 'TZ_INDIAN_MALDIVES',
            91 => 'TZ_AMERICA_TIJUANA',
            92 => 'TZ_AMERICA_HERMOSILLO',
            93 => 'TZ_AMERICA_MAZATLAN',
            94 => 'TZ_AMERICA_MEXICO_CITY',
            95 => 'TZ_ASIA_KUALA_LUMPUR',
            96 => 'TZ_AFRICA_LAGOS',
            97 => 'TZ_AMERICA_MANAGUA',
            98 => 'TZ_EUROPE_AMSTERDAM',
            99 => 'TZ_EUROPE_OSLO',
            100 => 'TZ_PACIFIC_AUCKLAND',
            101 => 'TZ_ASIA_MUSCAT',
            102 => 'TZ_AMERICA_PANAMA',
            103 => 'TZ_AMERICA_LIMA',
            104 => 'TZ_ASIA_MANILA',
            105 => 'TZ_ASIA_KARACHI',
            106 => 'TZ_EUROPE_WARSAW',
            107 => 'TZ_AMERICA_PUERTO_RICO',
            108 => 'TZ_ASIA_GAZA',
            109 => 'TZ_ATLANTIC_AZORES',
            110 => 'TZ_EUROPE_LISBON',
            111 => 'TZ_AMERICA_ASUNCION',
            112 => 'TZ_ASIA_QATAR',
            113 => 'TZ_EUROPE_BUCHAREST',
            114 => 'TZ_EUROPE_BELGRADE',
            115 => 'TZ_EUROPE_KALININGRAD',
            116 => 'TZ_EUROPE_MOSCOW',
            117 => 'TZ_EUROPE_SAMARA',
            118 => 'TZ_ASIA_YEKATERINBURG',
            119 => 'TZ_ASIA_OMSK',
            120 => 'TZ_ASIA_KRASNOYARSK',
            121 => 'TZ_ASIA_IRKUTSK',
            122 => 'TZ_ASIA_YAKUTSK',
            123 => 'TZ_ASIA_VLADIVOSTOK',
            124 => 'TZ_ASIA_MAGADAN',
            125 => 'TZ_ASIA_KAMCHATKA',
            126 => 'TZ_ASIA_ANADYR',
            127 => 'TZ_ASIA_RIYADH',
            128 => 'TZ_ASIA_SINGAPORE',
            129 => 'TZ_AFRICA_JOHANNESBURG',
            130 => 'TZ_EUROPE_STOCKHOLM',
            131 => 'TZ_ASIA_TAIPEI',
            132 => 'TZ_AFRICA_DAR_ES_SALAAM',
            133 => 'TZ_ASIA_BANGKOK',
            134 => 'TZ_AFRICA_TUNIS',
            135 => 'TZ_EUROPE_ISTANBUL',
            136 => 'TZ_AMERICA_MONTEVIDEO',
            137 => 'TZ_AMERICA_CARACAS',
            138 => 'TZ_ASIA_HO_CHI_MINH',
            139 => 'TZ_AFRICA_KINSHASA',
            140 => 'TZ_AFRICA_LUBUMBASHI',
            141 => 'TZ_AFRICA_HARARE',
            142 => 'TZ_AFRICA_LUSAKA',
        ];
    }

    /**
     * Lấy danh sách timezone options cho Meta (format cho select dropdown)
     * 
     * @return array [['value' => id, 'label' => name], ...]
     */
    public static function getMetaTimezoneOptions(): array
    {
        $timezones = self::getMetaTimezoneIds();
        $options = [];
        
        foreach ($timezones as $id => $name) {
            $options[] = [
                'value' => (string) $id,
                'label' => str_replace('TZ_', '', $name) . " ({$id})",
            ];
        }
        
        return $options;
    }

    /**
     * Lấy danh sách IANA timezone identifiers cho Google
     * Sử dụng Laravel DateTimeZone
     * 
     * @return array [identifier => identifier]
     */
    public static function getGoogleTimezoneIdentifiers(): array
    {
        return DateTimeZone::listIdentifiers();
    }

    /**
     * Lấy danh sách timezone options cho Google (format cho select dropdown)
     * 
     * @return array [['value' => identifier, 'label' => identifier], ...]
     */
    public static function getGoogleTimezoneOptions(): array
    {
        $identifiers = self::getGoogleTimezoneIdentifiers();
        $options = [];
        
        foreach ($identifiers as $identifier) {
            $options[] = [
                'value' => $identifier,
                'label' => $identifier,
            ];
        }
        
        // Sort by identifier for easier selection
        usort($options, fn($a, $b) => strcmp($a['label'], $b['label']));
        
        return $options;
    }

    /**
     * Lấy timezone name từ Meta timezone ID
     * 
     * @param int $timezoneId
     * @return string|null
     */
    public static function getMetaTimezoneName(int $timezoneId): ?string
    {
        $timezones = self::getMetaTimezoneIds();
        return $timezones[$timezoneId] ?? null;
    }
}

