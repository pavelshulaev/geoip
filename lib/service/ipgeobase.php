<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 27.02.2017
 * Time: 14:28
 *
 * @author Pavel Shulaev (https://rover-it.me)
 */
namespace Rover\GeoIp\Service;

use Rover\GeoIp\Helper\Charset;
use Rover\GeoIp\Service;

/**
 * Class IpGeoBase
 *
 * @package Rover\GeoIp\Service
 * @author  Pavel Shulaev (https://rover-it.me)
 */
class IpGeoBase extends Service
{
    const NAME = 'IpGeoBase';

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
            'sort'      => 100,
            'url'       => 'http://ipgeobase.ru:7020/geo?ip=' . $this->ip,
            'charset'   => Charset::WINDOWS_1251
        );
    }

    /**
     * @param        $string
     * @param string $language
     * @return array|mixed
     * @author Pavel Shulaev (https://rover-it.me)
     */
	protected function parse($string, $language = LANGUAGE_ID)
	{
		$data   = array();
		$pa     = array(
            self::FIELD__INETNUM        => '#<inetnum>(.*)</inetnum>#is',
            self::FIELD__COUNTRY_CODE   => '#<country>(.*)</country>#is',
            self::FIELD__CITY_NAME      => '#<city>(.*)</city>#is',
            self::FIELD__REGION_NAME    => '#<region>(.*)</region>#is',
            self::FIELD__DISTRICT       => '#<district>(.*)</district>#is',
            self::FIELD__LAT            => '#<lat>(.*)</lat>#is',
            self::FIELD__LNG            => '#<lng>(.*)</lng>#is',
            self::FIELD__MESSAGE        => '#<message>(.*)</message>#is'
        );

		foreach($pa as $key => $pattern)
			if(preg_match($pattern, $string, $out))
				$data[$key] = trim($out[1]);

		return $data;
	}
}