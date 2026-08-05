<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 24.10.2017
 * Time: 18:14
 *
 * @author Pavel Shulaev (https://rover-it.me)
 */

namespace Rover\GeoIp;

use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\SystemException;

/**
 * Class ServiceHandler
 *
 * @package Rover\GeoIp
 * @author  Pavel Shulaev (https://rover-it.me)
 */
class ServiceContainer
{
    /**
     * @var Service[][]
     */
    protected static array $services = [];

    /**
     * @param      $ip
     * @param      $charset
     * @param bool $reload
     * @return Service|null
     * @throws ArgumentOutOfRangeException
     * @throws SystemException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public static function getFirstValidService($ip, $charset, bool $reload = false): ?Service
    {
        $services = self::getList($ip, $charset, $reload);

        foreach ($services as $service) {
            if ($service->isValid()) {
                return $service;
            }
        }

        return null;
    }

    /**
     * @param       $ip
     * @param       $charset
     * @param false $reload
     * @return Service[]
     * @throws SystemException
     * @throws ArgumentOutOfRangeException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected static function getList($ip, $charset, bool $reload = false): array
    {
        $key = crc32($ip . $charset);

        if (!isset(self::$services[$key]) || $reload) {
            self::$services[$key] = Service::getList($ip, $charset);
        }

        return self::$services[$key];
    }

    /**
     * @param string $name
     * @param string $ip
     * @param string $charset
     * @return Service|null
     * @throws ArgumentOutOfRangeException
     * @throws SystemException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public static function getByName(string $name, string $ip, string $charset): ?Service
    {
        $name = trim($name);
        if (!mb_strlen($name)) {
            return null;
        }

        $services = self::getList($ip, $charset);
        foreach ($services as $service) {
            if (mb_strtoupper($service->getName()) == mb_strtoupper($name)) {
                return $service;
            }
        }

        return null;
    }
}