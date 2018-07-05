<?php
namespace Rover\GeoIp;

use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\SystemException;
use Rover\GeoIp\Helper\Charset;
use Rover\GeoIp\Helper\Ip;

/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 27.02.2017
 * Time: 14:27
 *
 * @author Pavel Shulaev (https://rover-it.me)
 */
abstract class Service
{
    const FIELD__IP             = 'ip';
    const FIELD__CITY_NAME      = 'city_name';
    const FIELD__REGION_CODE    = 'region_code';
    const FIELD__REGION_NAME    = 'region_name';
    const FIELD__COUNTRY_CODE   = 'country_code';
    const FIELD__COUNTRY_NAME   = 'country_name';
    const FIELD__COUNTRY_ID     = 'country_id';
    const FIELD__DISTRICT       = 'district';
    const FIELD__LAT            = 'lat';
    const FIELD__LNG            = 'lng';
    const FIELD__INETNUM        = 'inetnum';
    const FIELD__SERVICE        = 'service';
    const FIELD__MESSAGE        = 'message';
    const FIELD__ZIP_CODE       = 'zip_code';
    const FIELD__METRO_CODE     = 'metro_code';

    /** @var string */
	protected $ip;

    /** @var string */
	protected $charset;

    /** @var array */
	protected $data;

    /**
     * Service constructor.
     *
     * @param      $ip
     * @param null $charset
     * @throws ArgumentOutOfRangeException
     */
    public function __construct($ip, $charset = null)
    {
        if (!Ip::isV4($ip))
            throw new ArgumentOutOfRangeException('ip');

        $this->ip      = $ip;
        $this->charset = Charset::prepare($charset);
    }

    /**
     * @return mixed
     * @author Pavel Shulaev (https://rover-it.me)
     */
	abstract public function isActive();

    /**
     * @param        $string
     * @param string $language
     * @return mixed
     * @author Pavel Shulaev (https://rover-it.me)
     */
	abstract protected function parse($string, $language = LANGUAGE_ID);

    /**
     * @return mixed
     * @author Pavel Shulaev (https://rover-it.me)
     */
	abstract public function getManifest();

    /**
     * @param string $language
     * @param bool   $reload
     * @return array
     * @throws SystemException
     * @author Pavel Shulaev (https://rover-it.me)
     */
	public function getData($language = LANGUAGE_ID, $reload = false)
    {
        $this->loadData($language, $reload);

        return $this->data;
    }

    /**
     * @param $field
     * @return mixed
     * @throws SystemException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public function getManifestField($field)
    {
        $field      = trim($field);
        $manifest   = static::getManifest();

        if (strlen($field) && isset($manifest[$field]))
            return $manifest[$field];

        throw new SystemException('Field "' . $field . '"" not found in the manifest');
    }

    /**
     * @param string $language
     * @param bool   $reload
     * @throws SystemException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected function loadData($language = LANGUAGE_ID, $reload = false)
    {
        if (is_null($this->data) || $reload) {
            $string = static::load();
            $string = Charset::convert(static::getManifestField('charset'), $this->charset, $string);

            $data   = static::parse($string, $language);
            $data   = $this->addCountryData($data, $language);

            $data[self::FIELD__SERVICE] = static::getManifestField('name');
            $data[self::FIELD__IP]      = $this->ip;

            $this->data = $data;
        }
    }

    /**
     * @param              $data
     * @param mixed|string $language
     * @return mixed
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected function addCountryData($data, $language = LANGUAGE_ID)
    {
        $data[self::FIELD__COUNTRY_NAME]    = null;
        $data[self::FIELD__COUNTRY_ID]      = null;

        if (!$data[self::FIELD__COUNTRY_CODE])
            return $data;

        $data[self::FIELD__COUNTRY_ID] = GetCountryIdByCode(strtoupper($data[self::FIELD__COUNTRY_CODE]));

        if ($data[self::FIELD__COUNTRY_ID])
            $data[self::FIELD__COUNTRY_NAME] = GetCountryByID($data[self::FIELD__COUNTRY_ID], $language);

        return $data;
    }

    /**
     * @return mixed
     * @throws SystemException
     * @author Pavel Shulaev (https://rover-it.me)
     */
	protected function load()
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, static::getManifestField('url'));
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

		return curl_exec($ch);
	}

    /**
     * @param $ip
     * @param $charset
     * @return Service[]
     * @throws ArgumentOutOfRangeException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public static function getList($ip, $charset)
    {
        $list   = array();
        $patches= glob(dirname(__FILE__) . '/service/*');

        foreach ($patches as $patch) {
            $name       = basename($patch, '.php');

            $service = self::build($name, $ip, $charset);

            if ($service->isActive())
                $list[] = $service;
        }

        uasort($list, function(Service $service1, Service $service2) {
            $sort1 = $service1->getManifestField('sort');
            $sort2 = $service2->getManifestField('sort');

            if ($sort1 < $sort2)
                return -1;

            if ($sort1 > $sort2)
                return 1;

            return 0;
        });

        return $list;
    }

    /**
     * @param $name
     * @param $ip
     * @param $charset
     * @return mixed
     * @throws ArgumentOutOfRangeException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public static function build($name, $ip, $charset)
    {
        $className  = '\\Rover\\GeoIp\\Service\\' . $name;

        if (!class_exists($className))
            throw new ArgumentOutOfRangeException('className');

        $service = new $className($ip, $charset);
        if (!$service instanceof self)
            throw new ArgumentOutOfRangeException('className');

        return $service;
    }

    /**
     * @return bool
     * @throws SystemException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public function isValid()
    {
        $data = $this->getData();

        return isset($data[self::FIELD__COUNTRY_NAME]) && strlen($data[self::FIELD__COUNTRY_NAME]);
    }
}