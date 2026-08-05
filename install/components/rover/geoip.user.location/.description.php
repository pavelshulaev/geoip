<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 02.06.2015
 * Time: 21:28
 * @author Shulaev (pavel.shulaev@gmail.com)
 */

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

Loc::loadMessages(__FILE__);

$arComponentDescription = array(
	"NAME" => Loc::getMessage("ROVER_SL_NAME"),
	"DESCRIPTION" => Loc::getMessage("ROVER_SL_DESC"),
	"ICON" => "/images/search_form.gif",
	"CACHE_PATH" => "Y",
	"PATH" => array(
		"ID" => "rover",
		"NAME" => Loc::getMessage("ROVER_SL_SERVICE"),
		"CHILD" => array(
			"ID" => "geoip",
			"NAME" => Loc::getMessage("ROVER_SL_SERVICE_CHILD")
		)
	),
);
