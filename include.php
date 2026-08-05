<?php
if (PHP_VERSION_ID < 80100)
	return false;

\Bitrix\Main\EventManager::getInstance()->addEventHandler(
	'rover.geoip',
	'onBuildServiceList',
	[\Rover\GeoIp\EventHandler::class, 'onBuildServiceList']
);
