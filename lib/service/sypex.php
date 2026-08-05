<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 24.10.2017
 * Time: 20:09
 *
 * @author Pavel Shulaev (https://rover-it.me)
 */

namespace Rover\GeoIp\Service;

use Bitrix\Main\Config\Option;
use Rover\GeoIp\Service;

/**
 * Class Sypex
 *
 * @package Rover\GeoIp\Service
 * @author  Pavel Shulaev (https://rover-it.me)
 */
class Sypex extends Service
{
    protected static $allowedServers = [
        'ua.sxgeo.city',
        'de.sxgeo.city',
        'nl.sxgeo.city',
        'fr.sxgeo.city',
        'it.sxgeo.city',
        'uk.sxgeo.city',
        'us.sxgeo.city',
        'us2.sxgeo.city',
        'us3.sxgeo.city',
        'jp.sxgeo.city',
        'sg.sxgeo.city',
        'ru.sxgeo.city',
        'api.sypexgeo.net',
    ];

    /**
     * @return bool
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public function isActive(): bool
    {
        return Option::get('rover.geoip', 'sypex-active') == 'Y';
    }

    /**
     * @return array
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public function getManifest(): array
    {
        $key = trim(self::getKey());

        $url = mb_strlen($key)
            ? self::getServerName() . '/' . $key . '/json/' . urlencode($this->ip)
            : self::getServerName() . '/json/' . urlencode($this->ip);

        return [
            'sort'    => 100,
            'url'     => 'https://' . $url,
            'charset' => self::UTF_8
        ];
    }

    /**
     * @return string
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public function getKey(): string
    {
        $key = Option::get('rover.geoip', 'sypex-key', '');

        return preg_replace( "/[^a-zA-Z0-9\s]/", '', $key );
    }

    /**
     * @return string
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public function getServerName(): string
    {
        $server = trim(mb_strtolower(Option::get('rover.geoip', 'sypex-server', 'api.sypexgeo.net')));

        if (in_array($server, self::$allowedServers)) {
            return $server;
        }

        return 'api.sypexgeo.net';
    }

    /**
     * @param string $string
     * @param string $language
     * @return array
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected function parse(string $string, string $language = LANGUAGE_ID): array
    {
        $rawData = json_decode($string, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);//Json::decode($string);

        $data     = [];
        $language = strtolower(trim($language));

        if (!empty($rawData['error'])) {
            $data['ERROR'] = $rawData['error'];
        } else {
            $data[self::CITY_NAME] = $rawData['city']['name_' . $language] ?? '';
            $data[self::LAT]       = $rawData['city']['lat'] ?? '';
            $data[self::LNG]       = $rawData['city']['lon'] ?? '';

            $data[self::REGION_CODE] = $rawData['region']['iso'] ?? '';
            $data[self::REGION_NAME] = $rawData['region']['name_' . $language] ?? '';

            $data[self::COUNTRY_CODE] = $rawData['country']['iso'] ?? '';
            $data[self::COUNTRY_NAME] = $rawData['country']['name_' . $language] ?? '';
        }

        return $data;
    }
}