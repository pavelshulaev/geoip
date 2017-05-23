<?php
namespace Rover\GeoIp\Service;
use Bitrix\Main\ArgumentOutOfRangeException;

/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 27.02.2017
 * Time: 14:27
 *
 * @author Pavel Shulaev (http://rover-it.me)
 */
abstract class Base
{
	const CHARSET__UTF_8        = 'utf-8';
	const CHARSET__WINDOWS_1251 = 'windows-1251';

	abstract static function get($ip, $charset = null);

	/**
	 * @param $ip
	 * @return bool
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	public static function isValidIp($ip)
	{
		return (bool)preg_match("#^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$#", $ip);
	}

	/**
	 * @param      $link
	 * @param null $charset
	 * @return mixed|string
	 * @throws ArgumentOutOfRangeException
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	public static function load($link, $charset = null)
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $link);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 3);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

		$string = curl_exec($ch);

		if($charset && ($charset != self::CHARSET__WINDOWS_1251))
			$string = iconv(self::CHARSET__WINDOWS_1251, $charset, $string);

		return $string;
	}
}