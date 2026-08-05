<?php
/**
 * Created by PhpStorm.
 * User: Павел
 * Date: 6/3/2021
 * Time: 11:32 AM
 *
 * @author Pavel Shulaev (https://rover-it.me)
 */

namespace Rover\GeoIp\Helper;

/**
 * Class Tools
 *
 * @package Rover\GeoIp\Helper
 * @author  Pavel Shulaev (https://rover-it.me)
 */
class Tools
{
    /**
     * @param        $input
     * @param string $separator
     * @param bool   $strToUpper
     * @return string
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public static function fromCamelCase($input, string $separator = '_', bool $strToUpper = false): string
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);

        $ret = $matches[0];
        foreach ($ret as &$match)
            $match = $match == mb_strtoupper($match) ? mb_strtolower($match) : lcfirst($match);

        $result = implode($separator, $ret);
        if ($strToUpper)
            $result = mb_strtoupper($result);

        return $result;
    }
}