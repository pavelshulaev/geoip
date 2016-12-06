<?php
IncludeModuleLangFile(__FILE__);
define("ADMIN_MODULE_NAME", "rover.geoip");
define("ADMIN_MODULE_ICON", "<img src=\"/bitrix/images/iblock/iblock.gif\" width=\"48\" height=\"48\" border=\"0\" alt=\"".GetMessage("rover-gi__icon_hint")."\" title=\"".GetMessage("rover-gi__icon_hint")."\">");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/admin_tools.php");
