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
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Service\GeoIp\Manager;
use Bitrix\Main\SystemException;
use Rover\GeoIp\Helper\Ip;
use Rover\GeoIp\Helper\Tools;

Loc::LoadMessages(__FILE__);

/**
 * Class Base
 *
 * @package Rover\Geoip
 * @author  Shulaev (pavel.shulaev@gmail.com)
 * @method getIp
 * @method getCityName
 * @method getCountryCode
 * @method getCountryName
 * @method getCountryId
 * @method getRegionName
 * @method getRegionCode
 * @method getDistrict
 * @method getLat
 * @method getLng
 * @method getInetnum
 * @method getServiceName
 * @method getError
 */
class Location
{
    protected static array $instances = [];
    protected array        $data;
    protected string       $ip;
    protected string       $charset;
    protected string       $serviceName;
    protected string       $language;
    protected bool         $isBot;

    /**
     * @param string $ip
     * @param string $charset
     * @param string $serviceName
     * @param string $language
     * @throws ArgumentOutOfRangeException
     */
    private function __construct(string $ip, string $charset = LANG_CHARSET, string $serviceName = '',
        string $language = LANGUAGE_ID)
    {
        if (!Ip::isValid($ip)) {
            throw new ArgumentOutOfRangeException('ip');
        }

        $this->ip          = $ip;
        $this->charset     = trim($charset);
        $this->serviceName = trim($serviceName);
        $this->language    = $language;
        $this->isBot       = preg_match(
            "~(Google|Yahoo|Rambler|Bot|Yandex|Spider|Snoopy|Crawler|Finder|Mail|curl)~i",
            (string)(Context::getCurrent()->getServer()->getUserAgent() ?? '')
        );
    }

    /**
     * @param string $ip
     * @param string $charset
     * @param string $service
     * @param string $language
     * @return Location
     * @throws ArgumentOutOfRangeException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public static function getInstance(string $ip = '', string $charset = LANG_CHARSET, string $service = '',
        string $language = LANGUAGE_ID): Location
    {
        $ip = trim($ip);
        if (!mb_strlen($ip)) {
            $ip = Manager::getRealIp();
        }

        if (!isset(self::$instances[$ip])) {
            self::$instances[$ip] = new self($ip, $charset, $service, $language);
        }

        return self::$instances[$ip];
    }

    /**
     * @throws SystemException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected function loadData(): void
    {
        // info in cookie
        if (Cookie::checkIp($this->ip)) {
            $this->data = Cookie::get();

            return;
        }

        $this->reload();
    }

    /**
     * @param string $ip
     * @return $this
     * @throws SystemException
     * @throws ArgumentOutOfRangeException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public function reload(string $ip = ''): Location
    {
        $ip = trim($ip);
        if (!mb_strlen($ip)) {
            $ip = $this->ip;
        } elseif ($ip !== $this->ip) {
            if (isset(self::$instances[$this->ip]) && self::$instances[$this->ip] === $this) {
                unset(self::$instances[$this->ip]);
            }

            $this->ip              = $ip;
            self::$instances[$ip] = $this;
        }

        if (!Ip::isValid($ip)) {
            $this->data = ['error' => 'invalid ip'];
        } elseif ($this->isBot) {
            $this->data = ['error' => 'Bot query'];
        } else {
            $service = mb_strlen($this->serviceName)
                ? ServiceContainer::getByName($this->serviceName, $ip, $this->charset)
                : ServiceContainer::getFirstValidService($ip, $this->charset);

            if (!$service instanceof Service) {
                throw new SystemException('valid service not found');
            }

            $this->data = $service->getData($this->language, true);

            Cookie::set($this->data);
        }

        return $this;
    }

    /**
     * @return array
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public function getData(): array
    {
        // first request
        if (!isset($this->data)) {
            try {
                $this->loadData();
            } catch (\Throwable $e) {
                $this->data = [
                    'error' => $e->getMessage()
                ];
            }
        }

        return $this->data;
    }

    /**
     * @return bool
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public function isSuccess(): bool
    {
        return empty($this->getError());
    }

    /**
     * @param $field
     * @return mixed|null
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public function getField($field): mixed
    {
        $field = trim($field);

        if (mb_strlen($field)) {
            return $this->getData()[$field] ?? null;
        }

        return null;
    }

    /**
     * @param $methodName
     * @param $arguments
     * @return mixed|null
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public function __call($methodName, $arguments)
    {
        if (mb_strpos($methodName, 'get') === false) {
            return null;
        }

        $fieldName = mb_substr($methodName, 3);
        $fieldName = Tools::fromCamelCase($fieldName);

        return $this->getField($fieldName);
    }

    /**
     * @return string|null
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public static function getCurIp(): ?string
    {
        return Manager::getRealIp();
    }

    /**
     * @return string|null
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public function getLanguage(): ?string
    {
        return $this->language;
    }

    /**
     * @param string $language
     */
    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    /**
     * @return mixed
     * @author     Pavel Shulaev (https://rover-it.me)
     * @deprecated use getCityName
     */
    public function getCity(): string
    {
        return $this->getField(Service::CITY_NAME);
    }

    /**
     * @return mixed
     * @author Pavel Shulaev (https://rover-it.me)
     * @deprecated
     */
    public function getCountry(): string
    {
        return $this->getField(Service::COUNTRY_CODE);
    }

    /**
     * @return mixed
     * @author     Pavel Shulaev (https://rover-it.me)
     * @deprecated use getRegionName
     */
    public function getRegion(): string
    {
        return $this->getField(Service::REGION_NAME);
    }
}