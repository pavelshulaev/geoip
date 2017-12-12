<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Application;

Loc::LoadMessages(__FILE__);

/**
 * Class rover_geoip
 *
 * @author Pavel Shulaev (https://rover-it.me)
 */
class rover_geoip extends CModule
{
    var $MODULE_ID	= "rover.geoip";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;

    /**
     * rover_geoip constructor.
     */
    function __construct()
    {
        global $geoipErrors;

		$arModuleVersion	= array();
        $geoipErrors        = array();

        require(__DIR__ . "/version.php");

		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion)) {
			$this->MODULE_VERSION		= $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE	= $arModuleVersion["VERSION_DATE"];
	    } else
            $geoipErrors[] = Loc::getMessage('rover-gi__version_info_error');

        $this->MODULE_NAME			= Loc::getMessage('rover-gi__name');
        $this->MODULE_DESCRIPTION	= Loc::getMessage('rover-gi__descr');
        $this->PARTNER_NAME         = GetMessage('rover-gi__partner_name');
        $this->PARTNER_URI          = GetMessage('rover-gi__partner_uri');
    }

    /**
     * @author Pavel Shulaev (https://rover-it.me)
     */
    function DoInstall()
    {
        global $APPLICATION;
        $rights = $APPLICATION->GetGroupRight($this->MODULE_ID);

        if ($rights == "W")
		    $this->ProcessInstall();
	}

    /**
     * @author Pavel Shulaev (https://rover-it.me)
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
     * @author Pavel Shulaev (https://rover-it.me)
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
	 * @author Pavel Shulaev (https://rover-it.me)
	 */
	private function ProcessInstall()
    {
        global $geoipErrors;

        if (PHP_VERSION_ID < 50306)
            $geoipErrors[] = Loc::getMessage('rover-gi__php_version_error');

        if (!function_exists('curl_init'))
            $geoipErrors[] = Loc::getMessage('rover-gi__no-curl');

        $this->copyFiles();

        global $APPLICATION, $geoipErrors;

        if (empty($geoipErrors))
            ModuleManager::registerModule($this->MODULE_ID);

	    $APPLICATION->IncludeAdminFile(Loc::getMessage("rover-gi__install_title"),
            dirname(__FILE__) . "/message.php");
    }

	/**
	 * @author Pavel Shulaev (https://rover-it.me)
	 */
	private function ProcessUninstall()
	{
        $this->removeFiles();

        global $APPLICATION, $geoipErrors;

        if (empty($geoipErrors))
	        ModuleManager::unRegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile(Loc::getMessage("rover-gi__uninstall_title"),
            dirname(__FILE__) . "/unMessage.php");
	}

    /**
     * @author Pavel Shulaev (https://rover-it.me)
     */
	private function copyFiles()
    {
        global $geoipErrors;
      
        $documentRoot = Application::getDocumentRoot();

        if (!CopyDirFiles(dirname(__FILE__) . '/components/', $documentRoot . '/bitrix/components/', true, true))
            $geoipErrors[] = Loc::getMessage('rover-gi__copy_files_error');
    }

    /**
     * @author Pavel Shulaev (https://rover-it.me)
     */
    private function removeFiles()
    {
        DeleteDirFilesEx('/bitrix/components/rover/geoip.user.location');
    }
}