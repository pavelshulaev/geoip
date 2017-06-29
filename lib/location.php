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
use Rover\GeoIp\Service\Base;
use Rover\GeoIp\Service\FreeGeoIp;
use Rover\GeoIp\Service\IpGeoBase;

Loc::LoadMessages(__FILE__);

/**
 * Class Base
 *
 * @package Rover\Geoip
 * @author  Shulaev (pavel.shulaev@gmail.com)
 */
class Location
{
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
     * @param null $ip
     * @return $this
     * @throws ArgumentOutOfRangeException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public function reload($ip = null)
    {
        $ip = trim($ip);
        if (!$ip)
            $ip = $this->ip;

        if (!Base::isValidIp($ip))
            throw new ArgumentOutOfRangeException('ip');

        try{
            $data = IpGeoBase::get($ip, $this->charset);
        } catch (\Exception $e){
            $data = [];
        }

        if (!is_array($data))
            $data = [];

        // adding info, if needed
        if (!isset($data['city']) || !strlen($data['city']))
            try{
                $data = array_merge($data, FreeGeoIp::get($ip, $this->charset));
            } catch (\Exception $e) {

            }

       $this->data = array_merge(['ip' => $ip], $data);

       return $this;
    }

    /**
     * Location constructor.
     *
     * @param $ip
     * @param $charset
     * @throws ArgumentOutOfRangeException
     */
	private function __construct($ip, $charset)
	{
		if (!Base::isValidIp($ip))
			throw new ArgumentOutOfRangeException('ip');

		$this->ip       = $ip;
		$this->charset  = $charset;

		// info in cookie
		if (Cookie::checkIp($this->ip))
		{
			$this->data = Cookie::get();
			return;
		}

        $this->reload();

		Cookie::set($this->data);
	}

	/**
	 * @param null   $ip
	 * @param string $charset
	 * @return Location
	 * @throws ArgumentOutOfRangeException
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	public static function getInstance($ip = null, $charset = Base::CHARSET__UTF_8)
	{
		if (is_null($ip))
			$ip = self::getCurIp();

		if (!Base::isValidIp($ip))
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
			if(Base::isValidIp($ip))
				return $ip;

		return false;
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
		if (!isset($this->data['country_name'])){
			$country = Loc::getMessage('ROVER_GI_COUNTRY_' . strtoupper($this->getCountry()), null, $lang);
			if ($this->charset == Base::CHARSET__WINDOWS_1251)
				$country = iconv(Base::CHARSET__UTF_8, Base::CHARSET__WINDOWS_1251, $country);

			$this->data['country_name'] = $country;
		}

		return $this->data['country_name'];
	}

    /**
     * @return null
     * @author Pavel Shulaev (https://rover-it.me)
     */
	public function getCountryId()
    {
        $countries  = GetCountryArray();
        $key        = array_search($this->getCountryName(), $countries["reference"]);

        if ($key)
            return $countries["reference_id"][$key];

        return null;
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