<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 03.01.2016
 * Time: 22:56
 *
 * @author Pavel Shulaev (https://rover-it.me)
 */

namespace Rover\GeoIp;

use Bitrix\Main\Application;
use Bitrix\Main\Web\Cookie as BxCookie;

/**
 * Class Cookie
 *
 * @package Rover\GeoIp
 * @author  Pavel Shulaev (https://rover-it.me)
 */
class Cookie
{
	const LIFETIME  = 604800; // 1 week
	const NAME      = 'rover_geoip';

    /**
     * @param $data
     * @author Pavel Shulaev (https://rover-it.me)
     */
	public static function set($data)
	{
		$cookie = new BxCookie(self::NAME, serialize($data), time() + self::LIFETIME);

		Application::getInstance()
			->getContext()
			->getResponse()
			->addCookie($cookie);
	}

	/**
	 * @return mixed|null
	 * @author Pavel Shulaev (https://rover-it.me)
	 */
	public static function get()
	{
		$data = Application::getInstance()
			->getContext()
			->getRequest()
			->getCookie(self::NAME);

		return unserialize($data);
	}

	/**
	 * @param $ip
	 * @return bool
	 * @author Pavel Shulaev (https://rover-it.me)
	 */
	public static function checkIp($ip)
	{
		$data = self::get();

		if (!is_null($data) && isset($data['ip']))
			return $data['ip'] == $ip;

		return false;
	}
}