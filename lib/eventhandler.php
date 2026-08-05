<?php

namespace Rover\GeoIp;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Rover\GeoIp\Service\Sypex;

/**
 * Class EventHandler
 *
 * @package Rover\GeoIp
 * @author  Pavel Shulaev (https://rover-it.me)
 */
class EventHandler
{
    /**
     * Registers the module's built-in geoip services.
     *
     * @param Event $event
     * @return EventResult
     * @author Pavel Shulaev (https://rover-it.me)
     */
    public static function onBuildServiceList(Event $event): EventResult
    {
        return new EventResult(EventResult::SUCCESS, [
            'services' => [
                Sypex::class,
            ],
        ], 'rover.geoip');
    }
}
