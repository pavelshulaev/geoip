<?php

/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 02.10.2015
 * Time: 19:12
 *
 * @author Shulaev (pavel.shulaev@gmail.com)
 */
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main;
use \Bitrix\Main\Localization\Loc as Loc;
use \Bitrix\Main\ArgumentNullException;
use \Rover\GeoIp\Location;
use \Rover\GeoIp\Service;

/**
 * Class RoverSlider
 *
 * @author Pavel Shulaev (https://rover-it.me)
 */
class GeoIpUserLocation extends CBitrixComponent
{
    /**
     * @var
     */
    protected $fields;

    const INPUT__USER   = 'user';
    const INPUT__SELECT = 'select';
    const INPUT__SUBMIT = 'submit';
	
    /**
     * @author Pavel Shulaev (https://rover-it.me)
     */
	public function onIncludeComponentLang()
	{
		$this->includeComponentLang(basename(__FILE__));
		Loc::loadMessages(__FILE__);
	}

    /**
     * @param $params
     * @return mixed
     * @author Shulaev (pavel.shulaev@gmail.com)
     */
    public function onPrepareComponentParams($params)
    {

        $params['PAGE_SIZE'] = intval($params['PAGE_SIZE']);
        if ($params['PAGE_SIZE'] <= 0)
            $params['PAGE_SIZE'] = 20;

        return $params;
    }

    /**
     * @throws Main\LoaderException
     * @throws Main\SystemException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected function checkParams()
    {
        if (!Main\Loader::includeModule('rover.geoip'))
            throw new Main\SystemException(Loc::getMessage('rover-geoip__no-geoip-module'));

        if (!Main\Loader::includeModule('statistic'))
            throw new Main\SystemException(Loc::getMessage('rover-geoip__no-statistic-module'));
    }

    /**
     * @return array
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected function getSelect()
    {
        return array_unique(array_merge(array('ID', 'NAME', 'LAST_NAME'), $this->getFields()));
    }

    /**
     * @return Main\DB\Result
     * @throws Main\ArgumentException
     * @throws Main\ObjectPropertyException
     * @author Pavel Shulaev (https://rover-it.me)
     */
	protected function getUsersRaw()
	{
        $nav = new \Bitrix\Main\UI\PageNavigation("nav-users");
        $nav->allowAllRecords(false)
            ->setPageSize($this->arParams['PAGE_SIZE'])
            ->initFromUri();

	    $query  = array(
	        'select'        => $this->getSelect(),
            "count_total"   => true,
            'offset'        => $nav->getOffset(),
            'limit'         => $nav->getLimit(),
            'order'         => array('ID' => 'DESC')
        );

	    $users = Main\UserTable::getList($query);
        $nav->setRecordCount($users->getCount());
        $this->arResult['NAV'] = $nav;

	    return $users;
	}

    /**
     * @param          $user
     * @param Location $location
     * @return mixed
     * @throws ArgumentNullException
     * @throws Main\Db\SqlQueryException
     * @author Pavel Shulaev (https://rover-it.me)
     */
	protected function addLocation($user, Location $location)
    {
        $regIp = $this->getRegIp($user['ID']);
        if (!$regIp)
            return $user;

        $user['REG_IP'] = $regIp;

        try{
            $location->reload($regIp);
        } catch (\Exception $e) {
            return $user;
        }

        $user = $this->addFields($user, $location, 'CITY_FIELDS', Service::FIELD__CITY_NAME);
        $user = $this->addFields($user, $location, 'STATE_FIELDS', Service::FIELD__REGION_NAME);
        $user = $this->addFields($user, $location, 'COUNTRY_FIELDS', Service::FIELD__COUNTRY_ID);

        return $user;
    }

    /**
     * @return array
     * @throws ArgumentNullException
     * @throws Main\ArgumentException
     * @throws Main\Db\SqlQueryException
     * @throws Main\ObjectPropertyException
     * @author Pavel Shulaev (https://rover-it.me)
     */
	protected function getUsers()
    {
        $users      = $this->getUsersRaw();
        $location   = Location::getInstance();
        $result     = array();

        while ($user = $users->fetch())
        {
            if ($location instanceof Location)
                $user = $this->addLocation($user, $location);

            $result[] = $user;
        }

        return $result;
    }

    /**
     * @return array
     * @author Pavel Shulaev (https://rover-it.me)
     */
	protected function getFields()
    {
        if (is_null($this->fields)) {
            $fields = array_merge($this->arParams['CITY_FIELDS'],
                $this->arParams['STATE_FIELDS'],
                $this->arParams['COUNTRY_FIELDS']);

            $this->fields = array();

            foreach ($fields as $field) {
                $field = trim($field);
                if (!strlen($field))
                    continue;

                $this->fields[] = $field;
            }
        }

        return $this->fields;
    }

    /**
     * @throws Main\SystemException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected function update()
    {
        if (!$this->request->getPost(self::INPUT__SUBMIT))
            return;

        $userHandler= new CUser();
        $selected   = array_keys($this->request->getPost(self::INPUT__SELECT));
        $users      = $this->request->getPost(self::INPUT__USER);

        foreach ($selected as $userId)
        {
            if (!isset($users[$userId]))
                continue;

            if (!$userHandler->Update($userId, $users[$userId]))
                throw new Main\SystemException($userHandler->LAST_ERROR);
        }
    }

    /**
     * @throws Main\SystemException
     * @author Pavel Shulaev (https://rover-it.me)
     */
	protected function getResult()
	{
	    $this->arResult['USERS']            = $this->getUsers();
	    $this->arResult['FIELDS']           = $this->getSelect();
	    $this->arResult['LOCATION_FIELDS']  = $this->getFields();
	    $this->arResult['COUNTRIES']        = $this->getCountries();
	}

    /**
     * @return array
     * @author Pavel Shulaev (https://rover-it.me)
     */
	protected function getCountries()
    {
        $result     = array();
        $countries  = GetCountryArray();

        foreach ($countries['reference_id'] as $countryPos => $countryId)
            if (isset($countries['reference'][$countryPos]))
                $result[$countryId] = $countries['reference'][$countryPos];

        asort($result);

        $russia = $result[1];
        unset($result[1]);
        $result = array(0 => '-', 1 => $russia) + $result;

        return $result;
    }
    /**
     * @param          $user
     * @param Location $location
     * @param          $fieldType
     * @param          $locationType
     * @return mixed
     * @author Pavel Shulaev (https://rover-it.me)
     */
	protected function addFields($user, Location $location, $fieldType, $locationType)
    {
        $fieldType = trim($fieldType);
        if (!$fieldType)
            return $user;

        $locationType = trim($locationType);
        if (!strlen($locationType))
            return $user;

        if (!isset($this->arParams[$fieldType]))
            return $user;

        foreach ($this->arParams[$fieldType] as $field)
            $user['~' . $field] = $location->getField($locationType);

        return $user;
    }

    /**
     * @param $userId
     * @return null
     * @throws ArgumentNullException
     * @throws Main\Db\SqlQueryException
     * @author Pavel Shulaev (https://rover-it.me)
     */
	protected function getRegIp($userId)
    {
        $userId = intval($userId);
        if (!$userId)
            throw new ArgumentNullException('userId');

        $connection = Main\Application::getConnection();
        $sqlHelper  = $connection->getSqlHelper();

        $sql = 'SELECT IP_LAST FROM b_stat_session WHERE USER_ID=' . $sqlHelper->forSql($userId)
            . ' ORDER BY DATE_FIRST ASC LIMIT 1';

        $result = $connection->query($sql)->fetch();

        if (isset($result['IP_LAST']))
            return $result['IP_LAST'];

        return null;
    }

    /**
     * @param $user
     * @return bool
     * @author Pavel Shulaev (https://rover-it.me)
     */
	protected function checkUserFields($user)
    {
        $fields = $this->getFields();

        foreach ($fields as $field)
            if (!isset($user[$field]))
                return false;

        return true;
    }

	/**
	 * @author Shulaev (pavel.shulaev@gmail.com)
	 */
	public function executeComponent()
	{
        try {
            $this->setFrameMode(true);
            $this->checkParams();
            $this->update();
            $this->getResult();
            $this->includeComponentTemplate();
        } catch (Exception $e) {
            ShowError($e->getMessage());
        }
	}
}