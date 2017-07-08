<?php
namespace Rover\GeoIp\Service;

use Bitrix\Main\ArgumentOutOfRangeException;
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 27.02.2017
 * Time: 14:28
 *
 * @author Pavel Shulaev (http://rover-it.me)
 */
class IpGeoBase extends Base
{
	/**
	 * @param             $ip
	 * @param null|string $charset
	 * @return array
	 * @throws ArgumentOutOfRangeException
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	public static function get($ip, $charset = self::CHARSET__UTF_8)
	{
		if (!Ip::isV4($ip))
			throw new ArgumentOutOfRangeException('ip');

		$string = self::load('ipgeobase.ru:7020/geo?ip=' . $ip, $charset);

		return self::parse($string);
	}

	/**
	 * @param $string
	 * @return array
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	protected static function parse($string)
	{
		$data   = [];
		$pa     = [];

		$pa['inetnum']  = '#<inetnum>(.*)</inetnum>#is';
		$pa['country']  = '#<country>(.*)</country>#is';
		$pa['city']     = '#<city>(.*)</city>#is';
		$pa['region']   = '#<region>(.*)</region>#is';
		$pa['district'] = '#<district>(.*)</district>#is';
		$pa['lat']      = '#<lat>(.*)</lat>#is';
		$pa['lng']      = '#<lng>(.*)</lng>#is';

		foreach($pa as $key => $pattern)
			if(preg_match($pattern, $string, $out))
				$data[$key] = trim($out[1]);

		return $data;
	}
}