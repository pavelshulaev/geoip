<?php
namespace Rover\GeoIp;
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 03.01.2016
 * Time: 21:36
 *
 * @author Pavel Shulaev (https://rover-it.me)
 */
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Rover\GeoIp\Helper\Charset;
use Rover\GeoIp\Helper\Ip;

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
	protected static $instances = array();

	/**
	 * data of instance
	 * @var array
	 */
	protected $data;

    /**
     * @var string
     */
	protected $ip;

    /**
     * @var string
     */
	protected $charset;

    /**
     * @var string
     */
	protected $service;

    /**
     * Location constructor.
     *
     * @param        $ip
     * @param        $charset
     * @param string $service
     * @throws ArgumentOutOfRangeException
     */
    private function __construct($ip, $charset, $service = '')
    {
        if (!Ip::isValid($ip))
            throw new ArgumentOutOfRangeException('ip');

        $this->ip       = $ip;
        $this->charset  = Charset::prepare($charset);
        $this->service  = trim($service);
    }

    /**
     * @param string $ip
     * @param string $charset
     * @param string $service
     * @return self
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public static function getInstance($ip = '', $charset = Charset::AUTO, $service = '')
    {
        $ip = trim($ip);
        if (!strlen($ip))
            $ip = Ip::getCur();

        if (!isset(self::$instances[$ip]))
            self::$instances[$ip] = new self($ip, $charset, $service);

        return self::$instances[$ip];
    }

    /**
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected function loadData()
    {
        // info in cookie
        if (Cookie::checkIp($this->ip))
        {
            $this->data = Cookie::get();
            return;
        }

        $this->reload();
    }

    /**
     * @param $ip
     * @return $this
     * @throws SystemException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public function reload($ip = '')
    {
        $ip = trim($ip);
        if (!strlen($ip))
            $ip = $this->ip;

        $service = strlen($this->service)
            ? ServiceContainer::getByName($this->service, $ip, $this->charset)
            : ServiceContainer::getFirstValidService($ip, $this->charset);

        if (!$service instanceof Service)
            throw new SystemException('valid service not found');

        $this->data = $service->getData();

        Cookie::set($this->data);

        return $this;
    }

    /**
     * @return array|mixed|null
     * @author Pavel Shulaev (https://rover-it.me)
     */
	public function getData()
	{
	    // first request
	    if (is_null($this->data))
            $this->loadData();

	    return $this->data;
	}

    /**
     * @param $field
     * @return mixed|null
     * @author Pavel Shulaev (https://rover-it.me)
     */
	public function getField($field)
    {
        $data = $this->getData();

        $field = trim($field);

        if (strlen($field) && isset($data[$field]) )
            return $data[$field];

        return null;
    }

	/**
	 * @return mixed
	 * @author Pavel Shulaev (https://rover-it.me)
	 */
	public function getIp()
	{
		return $this->getField(Service::FIELD__IP);
	}

	/**
	 * @return mixed
	 * @author Pavel Shulaev (https://rover-it.me)
     * @deprecated use getCityName
	 */
	public function getCity()
	{
		return $this->getField(Service::FIELD__CITY_NAME);
	}

	/**
	 * @return mixed
	 * @author Pavel Shulaev (https://rover-it.me)
	 */
	public function getCityName()
	{
		return $this->getField(Service::FIELD__CITY_NAME);
	}

	/**
	 * @return mixed
	 * @author Pavel Shulaev (https://rover-it.me)
     * @deprecated
	 */
	public function getCountry()
	{
		return $this->getField(Service::FIELD__COUNTRY_CODE);
	}

	/**
	 * @return mixed
	 * @author Pavel Shulaev (https://rover-it.me)
	 */
	public function getCountryCode()
	{
		return $this->getField(Service::FIELD__COUNTRY_CODE);
	}

    /**
     * @return array|mixed|null
     * @author Pavel Shulaev (https://rover-it.me)
     */
	public function getCountryName()
	{
	    return $this->getField(Service::FIELD__COUNTRY_NAME);
	}

    /**
     * @return null
     * @author Pavel Shulaev (https://rover-it.me)
     */
	public function getCountryId()
    {
        return $this->getField(Service::FIELD__COUNTRY_ID);
    }

	/**
	 * @return mixed
	 * @author Pavel Shulaev (https://rover-it.me)
     * @deprecated use getRegionName
	 */
	public function getRegion()
	{
		return $this->getField(Service::FIELD__REGION_NAME);
	}

    /**
     * @return mixed|null
     * @author Pavel Shulaev (https://rover-it.me)
     */
	public function getRegionName()
	{
		return $this->getField(Service::FIELD__REGION_NAME);
	}

    /**
     * @return mixed|null
     * @author Pavel Shulaev (https://rover-it.me)
     */
	public function getRegionCode()
	{
		return $this->getField(Service::FIELD__REGION_CODE);
	}

	/**
	 * @return mixed
	 * @author Pavel Shulaev (https://rover-it.me)
	 */
	public function getDistrict()
	{
		return $this->getField(Service::FIELD__DISTRICT);
	}

	/**
	 * @return mixed
	 * @author Pavel Shulaev (https://rover-it.me)
	 */
	public function getLat()
	{
		return $this->getField(Service::FIELD__LAT);
	}

	/**
	 * @return array
	 * @author Pavel Shulaev (https://rover-it.me)
	 */
	public function getLng()
	{
		return $this->getField(Service::FIELD__LNG);
	}

	/**
	 * @return mixed
	 * @author Pavel Shulaev (https://rover-it.me)
	 */
	public function getInetnum()
	{
		return $this->getField(Service::FIELD__INETNUM);
	}

    /**
     * @return string
     * @author Pavel Shulaev (https://rover-it.me)
     */
	public function getService()
    {
        return $this->getField(Service::FIELD__SERVICE);
    }

    /**
     * @return bool
     * @author Pavel Shulaev (https://rover-it.me)
     */
	public static function getCurIp()
    {
        return Ip::getCur();
    }
}