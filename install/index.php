<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::LoadMessages(__FILE__);

class rover_geoip extends CModule
{
    var $MODULE_ID	= "rover.geoip";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;
	
    function __construct()
    {
		$arModuleVersion	= array();

        require(__DIR__ . "/version.php");

		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion)) {
			$this->MODULE_VERSION		= $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE	= $arModuleVersion["VERSION_DATE"];
	    } else
            $errors[] = Loc::getMessage('rover-gi__version_info_error');

        $this->MODULE_NAME			= Loc::getMessage('rover-gi__name');
        $this->MODULE_DESCRIPTION	= Loc::getMessage('rover-gi__descr');
        $this->PARTNER_NAME         = GetMessage('rover-gi__partner_name');
        $this->PARTNER_URI          = GetMessage('rover-gi__partner_uri');
    }

    /**
     * @author Pavel Shulaev (http://rover-it.me)
     */
    function DoInstall()
    {
        global $APPLICATION;
        $rights = $APPLICATION->GetGroupRight($this->MODULE_ID);

        if ($rights == "W")
		    $this->ProcessInstall();
	}

    /**
     * @author Pavel Shulaev (http://rover-it.me)
     */
    function DoUninstall()
    {
        global $APPLICATION;
        $rights = $APPLICATION->GetGroupRight($this->MODULE_ID);

        if ($rights == "W")
            $this->ProcessUninstall();
    }

    /**
     * @return array
     * @author Pavel Shulaev (http://rover-it.me)
     */
    function GetModuleRightsList()
    {
        return array(
            "reference_id" => array("D", "R", "W"),
            "reference" => array(
                Loc::getMessage('rover-gi__reference_deny'),
                Loc::getMessage('rover-gi__reference_read'),
                Loc::getMessage('rover-gi__reference_write')
            )
        );
    }

	/**
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	private function ProcessInstall()
    {
        global $APPLICATION, $errors;

        if (PHP_VERSION_ID < 50400)
            $errors[] = Loc::getMessage('rover-gi__php_version_error');

        if (!function_exists('curl_init'))
            $errors[] = Loc::getMessage('rover-gi__no-curl');

        if (empty($errors))
            ModuleManager::registerModule($this->MODULE_ID);

	    $APPLICATION->IncludeAdminFile(Loc::getMessage("rover-gi__install_title"),
            __DIR__ . "/message.php");
    }

	/**
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	private function ProcessUninstall()
	{
        global $APPLICATION, $errors;

        if (empty($errors))
	        ModuleManager::unRegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile(Loc::getMessage("rover-gi__uninstall_title"),
            __DIR__ . "/unMessage.php");
	}
}