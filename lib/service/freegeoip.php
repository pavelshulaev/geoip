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

use Bitrix\Main\ArgumentOutOfRangeException;

/**
 * Class FreeGeoIp
 *
 * @package Rover\GeoIp\Service
 * @author  Pavel Shulaev (https://rover-it.me)
 */
class FreeGeoIp extends Base
{
	/**
	 * @param             $ip
	 * @param null|string $charset
	 * @return array
	 * @throws ArgumentOutOfRangeException
	 * @author Pavel Shulaev (https://rover-it.me)
	 */
	public static function get($ip, $charset = self::CHARSET__UTF_8)
	{
		if (!Ip::isV4($ip))
			throw new ArgumentOutOfRangeException('ip');

		$string = self::load('freegeoip.net/xml/' . $ip, $charset);

		return self::parse($string);
	}

	/**
	 * @param $string
	 * @return array
	 * @author Pavel Shulaev (https://rover-it.me)
	 */
	protected static function parse($string)
	{
		$data   = [];
		$pa     = [];

		$pa['country']      = '#<countrycode>(.*)</countrycode>#is';
		$pa['country_name'] = '#<countryname>(.*)</countryname>#is';
		$pa['city']         = '#<city>(.*)</city>#is';
		$pa['region_code']  = '#<regioncode>(.*)</regioncode>#is';
		$pa['region']       = '#<regionname>(.*)</regionname>#is';
		$pa['zip_code']     = '#<zipcode>(.*)</zipcode>#is';
		$pa['lat']          = '#<latitude>(.*)</latitude>#is';
		$pa['lng']          = '#<longitude>(.*)</longitude>#is';
		$pa['metro_code']   = '#<metrocode>(.*)</metrocode>#is';

		foreach($pa as $key => $pattern)
			if(preg_match($pattern, $string, $out))
				$data[$key] = trim($out[1]);

		return $data;
	}
}