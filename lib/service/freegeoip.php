<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 27.02.2017
 * Time: 14:43
 *
 * @author Pavel Shulaev (https://rover-it.me)
 */

namespace Rover\GeoIp\Service;

use Rover\GeoIp\Helper\Charset;
use Rover\GeoIp\Service;

/**
 * Class FreeGeoIp
 *
 * @package Rover\GeoIp\Service
 * @author  Pavel Shulaev (https://rover-it.me)
 */
class FreeGeoIp extends Service
{
    const NAME = 'FreeGeoIp';

    /**
     * @return bool
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public function isActive()
    {
        return false;
    }

    /**
     * @return array
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public function getManifest()
    {
        return array(
            'name'      => self::NAME,
            'sort'      => 200,
            'url'       => 'freegeoip.net/xml/' . $this->ip,
            'charset'   => Charset::UTF_8
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
		$pa     = array();

		$pa[self::FIELD__COUNTRY_CODE] = '#<countrycode>(.*)</countrycode>#is';
		$pa[self::FIELD__COUNTRY_NAME] = '#<countryname>(.*)</countryname>#is';
		$pa[self::FIELD__CITY_NAME]    = '#<city>(.*)</city>#is';
		$pa[self::FIELD__REGION_CODE]  = '#<regioncode>(.*)</regioncode>#is';
		$pa[self::FIELD__REGION_NAME]  = '#<regionname>(.*)</regionname>#is';
		$pa[self::FIELD__ZIP_CODE]     = '#<zipcode>(.*)</zipcode>#is';
		$pa[self::FIELD__LAT]          = '#<latitude>(.*)</latitude>#is';
		$pa[self::FIELD__LNG]          = '#<longitude>(.*)</longitude>#is';
		$pa[self::FIELD__METRO_CODE]   = '#<metrocode>(.*)</metrocode>#is';

		foreach($pa as $key => $pattern)
			if(preg_match($pattern, $string, $out))
				$data[$key] = trim($out[1]);

		return $data;
	}
}