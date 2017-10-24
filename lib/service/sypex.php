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

class Sypex extends Service
{
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
            'name'      => 'Sypex',
            'sort'      => 150,
            'url'       => 'api.sypexgeo.net/json/' . $this->ip,
            'charset'   => Charset::UTF_8
        );
    }

    /**
     * @param $string
     * @return array
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected function parse($string)
    {
        $rawData    = Json::decode($string);
        $data       = array();

        $data[self::FIELD__CITY_NAME]   = isset($rawData['city']['name_ru']) ? $rawData['city']['name_ru'] : '';
        $data[self::FIELD__LAT]         = isset($rawData['city']['lat']) ? $rawData['city']['lat'] : '';
        $data[self::FIELD__LNG]         = isset($rawData['city']['lon']) ? $rawData['city']['lon'] : '';

        $data[self::FIELD__REGION_CODE] = isset($rawData['region']['iso']) ? $rawData['region']['iso'] : '';
        $data[self::FIELD__REGION_NAME] = isset($rawData['region']['name_ru']) ? $rawData['region']['name_ru'] : '';

        $data[self::FIELD__COUNTRY_CODE]= isset($rawData['country']['iso']) ? $rawData['country']['iso'] : '';
        $data[self::FIELD__COUNTRY_NAME]= isset($rawData['country']['name_ru']) ? $rawData['country']['name_ru'] : '';

        return $data;
    }
}