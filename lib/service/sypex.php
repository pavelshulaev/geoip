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

use Rover\GeoIp\Helper\Charset;
use Rover\GeoIp\Service;
use Bitrix\Main\Web\Json;

/**
 * Class Sypex
 *
 * @package Rover\GeoIp\Service
 * @author  Pavel Shulaev (https://rover-it.me)
 */
class Sypex extends Service
{
    const NAME = 'Sypex';

    /**
     * @return bool
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public function isActive()
    {
        return true;
    }

    /**
     * @return array
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public function getManifest()
    {
        return array(
            'name'      => self::NAME,
            'sort'      => 150,
            'url'       => 'api.sypexgeo.net/json/' . $this->ip,
            'charset'   => Charset::UTF_8
        );
    }

    /**
     * @param        $string
     * @param string $language
     * @return array|mixed
     * @throws \Bitrix\Main\ArgumentException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected function parse($string, $language = LANGUAGE_ID)
    {
        $rawData    = Json::decode($string);
        $data       = array();
        $language   = strtolower(trim($language));

        $data[self::FIELD__CITY_NAME]   = isset($rawData['city']['name_' . $language]) ? $rawData['city']['name_' . $language] : '';
        $data[self::FIELD__LAT]         = isset($rawData['city']['lat']) ? $rawData['city']['lat'] : '';
        $data[self::FIELD__LNG]         = isset($rawData['city']['lon']) ? $rawData['city']['lon'] : '';

        $data[self::FIELD__REGION_CODE] = isset($rawData['region']['iso']) ? $rawData['region']['iso'] : '';
        $data[self::FIELD__REGION_NAME] = isset($rawData['region']['name_' . $language]) ? $rawData['region']['name_' . $language] : '';

        $data[self::FIELD__COUNTRY_CODE]= isset($rawData['country']['iso']) ? $rawData['country']['iso'] : '';
        $data[self::FIELD__COUNTRY_NAME]= isset($rawData['country']['name_' . $language]) ? $rawData['country']['name_' . $language] : '';

        return $data;
    }
}