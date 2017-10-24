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
    protected static $services = array();

    /**
     * @param      $ip
     * @param      $charset
     * @param bool $reload
     * @return Service
     * @throws SystemException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public static function getFirstValidService($ip, $charset, $reload = false)
    {
        $services = self::getServices($ip, $charset, $reload);

        foreach ($services as $service)
            if ($service->isValid())
                return $service;

        return null;
    }

    /**
     * @param      $ip
     * @param      $charset
     * @param bool $reload
     * @return Service[]
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected static function getServices($ip, $charset, $reload = false)
    {
        $key = md5($ip . $charset);

        if (!isset(self::$services[$key]) || $reload)
            self::$services[$key] = Service::getList($ip, $charset);

        return self::$services[$key];
    }

    /**
     * @param $name
     * @param $ip
     * @param $charset
     * @return null|Service
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public static function getByName($name, $ip, $charset)
    {
        $name = trim($name);
        if (!strlen($name))
            return null;

        $services = self::getServices($ip, $charset);
        foreach ($services as $service)
            if (strtoupper($service->getManifestField('name')) == strtoupper($name))
                return $service;

        return null;
    }
}