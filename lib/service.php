<?php

namespace Rover\GeoIp;

use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\SystemException;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Web\HttpClient;
use Rover\GeoIp\Helper\Ip;
use Throwable;

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
    const IP           = 'ip';
    const CITY_NAME    = 'city_name';
    const REGION_CODE  = 'region_code';
    const REGION_NAME  = 'region_name';
    const COUNTRY_CODE = 'country_code';
    const COUNTRY_NAME = 'country_name';
    const COUNTRY_ID   = 'country_id';
    const DISTRICT     = 'district';
    const LAT          = 'lat';
    const LNG          = 'lng';
    const INETNUM      = 'inetnum';
    const SERVICE_NAME = 'service_name';
    const MESSAGE      = 'message';
    const ZIP_CODE     = 'zip_code';
    const METRO_CODE   = 'metro_code';

    const UTF_8        = 'utf-8';
    const WINDOWS_1251 = 'windows-1251';

    protected string $ip;
    protected string $charset;
    protected array  $data;

    /**
     * Service constructor.
     *
     * @param string $ip
     * @param string $charset
     * @throws ArgumentOutOfRangeException
     */
    public function __construct(string $ip, string $charset = LANG_CHARSET)
    {
        if (!Ip::isV4($ip)) {
            throw new ArgumentOutOfRangeException('ip');
        }

        $this->ip      = $ip;
        $this->charset = trim($charset);
    }

    /**
     * @return mixed
     * @author Pavel Shulaev (https://rover-it.me)
     */
    abstract public function isActive(): bool;

    /**
     * @param string $string
     * @param string $language
     * @return mixed
     * @author Pavel Shulaev (https://rover-it.me)
     */
    abstract protected function parse(string $string, string $language = LANGUAGE_ID);

    /**
     * @return mixed
     * @author Pavel Shulaev (https://rover-it.me)
     */
    abstract public function getManifest();

    /**
     * @param string $language
     * @param bool $reload
     * @return array
     * @throws SystemException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public function getData(string $language = LANGUAGE_ID, bool $reload = false): array
    {
        $this->loadData($language, $reload);

        return $this->data;
    }

    /**
     * @return string
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public static function getName(): string
    {
        $className = get_called_class();

        return basename(str_replace('\\', '/', $className));
    }

    /**
     * @param $field
     * @return mixed
     * @throws SystemException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public function getManifestField($field): string
    {
        $field    = trim($field);
        $manifest = static::getManifest();

        if (strlen($field) && isset($manifest[$field])) {
            return $manifest[$field];
        }

        throw new SystemException('Field "' . $field . '"" not found in the manifest');
    }

    /**
     * @param string $language
     * @param bool $reload
     * @throws SystemException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected function loadData(string $language = LANGUAGE_ID, bool $reload = false): void
    {
        if (!isset($this->data) || $reload) {
            $string = static::load();
            $data   = static::parse($string, $language);
            $data   = Encoding::convertEncoding($data, static::getManifestField('charset'), $this->charset);

            $data = $this->addCountryData($data, $language);

            $data[self::SERVICE_NAME] = static::getName();
            $data[self::IP]           = $this->ip;

            $this->data = $data;
        }
    }

    /**
     * @param array $data
     * @param mixed|string $language
     * @return array
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected function addCountryData(array $data, string $language = LANGUAGE_ID): array
    {
        $data[self::COUNTRY_NAME] = null;
        $data[self::COUNTRY_ID]   = null;

        if (!$data[self::COUNTRY_CODE]) {
            return $data;
        }

        $data[self::COUNTRY_ID] = GetCountryIdByCode(strtoupper($data[self::COUNTRY_CODE]));

        if ($data[self::COUNTRY_ID]) {
            $data[self::COUNTRY_NAME] = GetCountryByID($data[self::COUNTRY_ID], $language);
        }

        return $data;
    }

    /**
     * @return string
     * @throws SystemException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected function load(): string
    {

        $httpClient = new HttpClient();
        $httpClient->setTimeout(Option::get('rover.geoip', 'curl-timeout', 200));
        $httpClient->get(static::getManifestField('url'));

        return $httpClient->getResult();
    }

    /**
     * @param $ip
     * @param $charset
     * @return Service[]
     * @throws SystemException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public static function getList($ip, $charset): array
    {
        $list = [];

        foreach (self::getClassNames($ip, $charset) as $className) {
            try {
                $service = self::build($className, $ip, $charset);
            } catch (Throwable $e) {
                continue;
            }

            if ($service->isActive()) {
                $list[] = $service;
            }
        }

        uasort($list, function (Service $service1, Service $service2) {
            $sort1 = $service1->getManifestField('sort');
            $sort2 = $service2->getManifestField('sort');

            if ($sort1 < $sort2) {
                return -1;
            }

            if ($sort1 > $sort2) {
                return 1;
            }

            return 0;
        });

        return $list;
    }

    /**
     * Collects geoip service class names registered via the "onBuildServiceList" event.
     *
     * @param $ip
     * @param $charset
     * @return string[]
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected static function getClassNames($ip, $charset): array
    {
        $classNames = [];

        $event = new Event('rover.geoip', 'onBuildServiceList', ['ip' => $ip, 'charset' => $charset]);
        $event->send();

        foreach ($event->getResults() as $eventResult) {
            if ($eventResult->getType() !== EventResult::SUCCESS) {
                continue;
            }

            $parameters = $eventResult->getParameters();
            if (!empty($parameters['services']) && is_array($parameters['services'])) {
                $classNames = array_merge($classNames, $parameters['services']);
            }
        }

        return array_unique($classNames);
    }

    /**
     * @param string $className fully qualified name of a class extending Service
     * @param $ip
     * @param $charset
     * @return Service
     * @throws ArgumentOutOfRangeException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public static function build(string $className, $ip, $charset): Service
    {
        if (!class_exists($className) || !is_subclass_of($className, self::class)) {
            throw new ArgumentOutOfRangeException('className');
        }

        return new $className($ip, $charset);
    }

    /**
     * @return bool
     * @throws SystemException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public function isValid(): bool
    {
        $data = $this->getData();

        return isset($data[self::COUNTRY_NAME]) && strlen($data[self::COUNTRY_NAME]);
    }
}