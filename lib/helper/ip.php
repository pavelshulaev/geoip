<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 08.07.2017
 * Time: 14:18
 *
 * @author Pavel Shulaev (https://rover-it.me)
 */

namespace Rover\GeoIp\Helper;

use Bitrix\Main\Service\GeoIp\Manager;

/**
 * Class Ip
 *
 * @package Rover\GeoIp\Service
 * @author  Pavel Shulaev (https://rover-it.me)
 */
class Ip
{
    /**
     * @return string|null
     * @author Pavel Shulaev (https://rover-it.me)
     * @deprecated
     */
    public static function getCur(): ?string
    {
        return Manager::getRealIp();
    }

    /**
     * @param $ip
     * @return bool
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public static function isV4($ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);

    }

    /**
     * @param $ip
     * @return bool
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public static function isV6($ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    /**
     * @param $ip
     * @return bool
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public static function isValid($ip): bool
    {
        return self::isV4($ip) || self::isV6($ip);
    }
}