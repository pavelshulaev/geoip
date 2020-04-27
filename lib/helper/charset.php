<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 24.10.2017
 * Time: 18:05
 *
 * @author Pavel Shulaev (https://rover-it.me)
 */

namespace Rover\GeoIp\Helper;

class Charset
{
    const AUTO         = 'auto';
    const UTF_8        = 'utf-8';
    const WINDOWS_1251 = 'windows-1251';

    /**
     * @param $charsetFrom
     * @param $charsetTo
     * @param $string
     * @return string
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public static function convert($charsetFrom, $charsetTo, $string)
    {
        if ($charsetFrom == $charsetTo)
            return $string;

        return iconv($charsetFrom, $charsetTo . '//IGNORE', $string);
    }

    /* Use it for json_encode some corrupt UTF-8 chars
 * useful for = malformed utf-8 characters possibly incorrectly encoded by json_encode
 */
    public  static function convertArray( $data, $charsetTo, $charsetFrom ) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::convertArray($value, $charsetTo, $charsetFrom);
            }
        } elseif (is_string($data)) {
            return self::convert($charsetFrom, $charsetTo, $data);
        }
        return $data;
    }

    /**
     * @param $charsetTo
     * @param $string
     * @return string
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public static function convertFromWindows1251($charsetTo, $string)
    {
        return self::convert(self::WINDOWS_1251, $charsetTo, $string);
    }

    /**
     * @param $charsetTo
     * @param $string
     * @return string
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public static function convertFromUtf8($charsetTo, $string)
    {
        return self::convert(self::UTF_8, $charsetTo, $string);
    }

    /**
     * @param $charset
     * @return mixed|string
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public static function prepare($charset)
    {
        $charset = trim($charset);
        if (!strlen($charset))
            $charset = self::AUTO;

        if ($charset == Charset::AUTO)
            $charset = LANG_CHARSET;

        return $charset;
    }
}