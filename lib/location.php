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

    const FIELD__IP         = 'ip';
    const FIELD__CITY       = 'city';
    const FIELD__REGION     = 'region';
    const FIELD__COUNTRY    = 'country';
    const FIELD__COUNTRY_NAME   = 'country_name';
    const FIELD__COUNTRY_ID = 'country_id';
    const FIELD__DISTRICT   = 'district';
    const FIELD__LAT        = 'lat';
    const FIELD__LNG        = 'lng';
    const FIELD__INETNUM    = 'inetnum';
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
     * @var bool
     */
	protected $requestFlag = false;

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
    }

    /**
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected function request()
    {
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

        $data = $this->addCountryData($data);

        $this->data = array_merge(['ip' => $ip], $data);

        return $this;
    }

    /**
     * @param $data
     * @return mixed
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected function addCountryData($data)
    {
        $data[self::FIELD__COUNTRY_NAME]    = null;
        $data[self::FIELD__COUNTRY_ID]      = null;

        if (!$data[self::FIELD__COUNTRY])
            return $data;

        $data[self::FIELD__COUNTRY_ID] = GetCountryIdByCode(strtoupper($data[self::FIELD__COUNTRY]));

        if ($data[self::FIELD__COUNTRY_ID])
            $data[self::FIELD__COUNTRY_NAME] = GetCountryByID($data[self::FIELD__COUNTRY_ID]);

        return $data;
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
     * @param null $field
     * @return array|mixed|null
     * @author Pavel Shulaev (https://rover-it.me)
     */
	public function getData($field = null)
	{
	    // first request
	    if (!$this->requestFlag){
            $this->request();
            $this->requestFlag = true;
        }

        $field = trim($field);

	    if (!strlen($field))
	        return $this->data;

	    if (isset($this->data[$field]))
            return $this->data[$field];

		return null;
	}

	/**
	 * @return mixed
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	public function getIp()
	{
		return $this->getData(self::FIELD__IP);
	}

	/**
	 * @return mixed
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	public function getCity()
	{
		return $this->getData(self::FIELD__CITY);
	}

	/**
	 * @return mixed
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	public function getCountry()
	{
		return $this->getData(self::FIELD__COUNTRY);
	}

    /**
     * @return array|mixed|null
     * @author Pavel Shulaev (https://rover-it.me)
     */
	public function getCountryName()
	{
	    return $this->getData(self::FIELD__COUNTRY_NAME);
	}

    /**
     * @return null
     * @author Pavel Shulaev (https://rover-it.me)
     */
	public function getCountryId()
    {
        return $this->getData(self::FIELD__COUNTRY_ID);
    }

	/**
	 * @return mixed
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	public function getRegion()
	{
		return $this->getData(self::FIELD__REGION);
	}

	/**
	 * @return mixed
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	public function getDistrict()
	{
		return $this->getData(self::FIELD__DISTRICT);
	}

	/**
	 * @return mixed
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	public function getLat()
	{
		return $this->getData(self::FIELD__LAT);
	}

	/**
	 * @return array
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	public function getLng()
	{
		return $this->getData(self::FIELD__LNG);
	}

	/**
	 * @return mixed
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	public function getInetnum()
	{
		return $this->getData(self::FIELD__INETNUM);
	}
}