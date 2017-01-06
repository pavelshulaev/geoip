<?php
namespace Rover\GeoIp;
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 03.01.2016
 * Time: 21:36
 *
 * @author Pavel Shulaev (http://rover-it.me)
 */
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Localization\Loc;

Loc::LoadMessages(__FILE__);

/**
 * Class Base
 *
 * @package Rover\Geoip
 * @author  Shulaev (pavel.shulaev@gmail.com)
 */
class Location
{

	const CHARSET__UTF_8        = 'utf-8';
	const CHARSET__WINDOWS_1251 = 'windows-1251';

	/**
	 * instances
	 * @var array
	 */
	protected static $instances = [];

	/**
	 * data of instance
	 * @var array
	 */
	protected $data = [];

	/**
	 * @param $ip
	 * @param $charset
	 * @throws ArgumentOutOfRangeException
	 */
	private function __construct($ip, $charset)
	{
		if (!self::isValidIp($ip))
			throw new ArgumentOutOfRangeException('ip');

		$this->ip       = $ip;
		$this->charset  = $charset;

		// info in cookie
		if (Cookie::checkIp($this->ip))
		{
			$this->data = Cookie::get();
			return;
		}

		$data = $this->load();
		$this->data = array_merge(['ip' => $this->ip], $data);;

		Cookie::set($this->data);
	}


	/**
	 * @param null   $ip
	 * @param string $charset
	 * @return Location
	 * @throws ArgumentOutOfRangeException
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	public static function getInstance($ip = null, $charset = self::CHARSET__UTF_8)
	{
		if (is_null($ip))
			$ip = self::getCurIp();

		if (!self::isValidIp($ip))
			throw new ArgumentOutOfRangeException('ip');

		if (!isset(self::$instances[$ip]))
			self::$instances[$ip] = new self($ip, $charset);

		return self::$instances[$ip];
	}

	/**
	 * @return bool
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	public static function getCurIp()
	{
		$ips = [];
		$server = Application::getInstance()->getContext()->getServer();

		if ($server->get('HTTP_X_FORWARDED_FOR'))
			$ips[] = trim(strtok($server->get('HTTP_X_FORWARDED_FOR'), ','));

		if ($server->get('HTTP_CLIENT_IP'))
			$ips[] = $server->get('HTTP_CLIENT_IP');

		if ($server->get('REMOTE_ADDR'))
			$ips[] = $server->get('REMOTE_ADDR');

		if ($server->get('HTTP_X_REAL_IP'))
			$ips[] = $server->get('HTTP_X_REAL_IP');

		foreach($ips as $ip)
			if(self::isValidIp($ip))
				return $ip;

		return false;
	}

	/**
	 * @param $ip
	 * @return bool
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	protected static function isValidIp($ip)
	{
		return boolval(preg_match("#^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$#", $ip));
	}

	/**
	 * @return array
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	protected function load()
	{
		// получаем данные по ip
		$link = 'ipgeobase.ru:7020/geo?ip=' . $this->ip;

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $link);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 3);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

		$string = curl_exec($ch);

		if($this->charset && ($this->charset != self::CHARSET__WINDOWS_1251))
			$string = iconv(self::CHARSET__WINDOWS_1251, $this->charset, $string);

		return $this->parse($string);
	}

	/**
	 * @param $string
	 * @return array
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	protected function parse($string)
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

	/**
	 * @return array
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * @return mixed
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	public function getIp()
	{
		return $this->data['ip'];
	}

	/**
	 * @return mixed
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	public function getCity()
	{
		return $this->data['city'];
	}

	/**
	 * @return mixed
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	public function getCountry()
	{
		return $this->data['country'];
	}

	/**
	 * @param string $lang
	 * @return string
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	public function getCountryName($lang = LANGUAGE_ID)
	{
		return Loc::getMessage('ROVER_GI_COUNTRY_' . strtoupper($this->getCountry()),
			null, $lang);
	}

	/**
	 * @return mixed
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	public function getRegion()
	{
		return $this->data['region'];
	}

	/**
	 * @return mixed
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	public function getDistrict()
	{
		return $this->data['district'];
	}

	/**
	 * @return mixed
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	public function getLat()
	{
		return $this->data['lat'];
	}

	/**
	 * @return array
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	public function getLng()
	{
		return $this->data['lng'];
	}

	/**
	 * @return mixed
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	public function getInetnum()
	{
		return $this->data['inetnum'];
	}
}