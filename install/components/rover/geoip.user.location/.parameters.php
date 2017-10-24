<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/**
 * @var array $arCurrentValues
 */
use \Bitrix\Main\Localization\Loc;
use \Rover\Params;
use \Bitrix\Main\SystemException;
use \Bitrix\Main\Loader;


Loc::loadMessages(__FILE__);



$arComponentParameters = array(
	"PARAMETERS" => array(
	    'PAGE_SIZE' => array(
            'PARENT'    => 'DATA_SOURCE',
            'NAME'      => Loc::getMessage('rover-gi-ul__page-size'),
            'TYPE' => 'TEXT',
            'DEFAULT' => 20
        ),
		"CITY_FIELDS" => array(
			'PARENT'    => 'DATA_SOURCE',
			'NAME'      => Loc::getMessage('rover-gi-ul__field-city'),
			"TYPE"      => "LIST",
			"VALUES"    => array(
			    'PERSONAL_CITY' => Loc::getMessage('rover-gi-ul__field-city-PERSONAL_CITY'),
			    'WORK_CITY'     => Loc::getMessage('rover-gi-ul__field-city-WORK_CITY')
            ),
			"MULTIPLE"   => "Y",
        ),
		"STATE_FIELDS" => array(
			'PARENT'    => 'DATA_SOURCE',
			'NAME'      => Loc::getMessage('rover-gi-ul__field-state'),
			"TYPE"      => "LIST",
			"VALUES"    => array(
			    'PERSONAL_STATE'=> Loc::getMessage('rover-gi-ul__field-state-PERSONAL_STATE'),
			    'WORK_STATE'    => Loc::getMessage('rover-gi-ul__field-state-WORK_STATE')
            ),
			"MULTIPLE"   => "Y",
        ),
		"COUNTRY_FIELDS" => array(
			'PARENT'    => 'DATA_SOURCE',
			'NAME'      => Loc::getMessage('rover-gi-ul__field-country'),
			"TYPE"      => "LIST",
			"VALUES"    => array(
			    'PERSONAL_COUNTRY'  => Loc::getMessage('rover-gi-ul__field-country-PERSONAL_COUNTRY'),
			    'WORK_COUNTRY'      => Loc::getMessage('rover-gi-ul__field-country-WORK_COUNTRY')
            ),
			"MULTIPLE"   => "Y",
        ),

    ),
);