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

/**
 * Class RoverSlider
 *
 * @author Pavel Shulaev (http://rover-it.me)
 */
class GeoIpUserLocation extends CBitrixComponent
{
    protected $fields;

    const INPUT__USER   = 'user';
    const INPUT__SELECT = 'select';
    const INPUT__SUBMIT = 'submit';
	/**
	 * @throws Main\LoaderException
	 * @throws Main\SystemException
	 * @author Shulaev (pavel.shulaev@gmail.com)
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
     * @throws Main\SystemException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected function checkParams()
    {
        if (!Main\Loader::includeModule('rover.geoip'))
            throw new Main\SystemException('no rover.geoip module');
    }

    /**
     * @return array
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected function getSelect()
    {
        return array_unique(array_merge(['ID', 'NAME', 'LAST_NAME'], $this->getFields()));
    }

    /**
     * @return Main\DB\Result
     * @author Pavel Shulaev (https://rover-it.me)
     */
	protected function getUsersRaw()
	{
        $nav = new \Bitrix\Main\UI\PageNavigation("nav-users");
        $nav->allowAllRecords(false)
            ->setPageSize($this->arParams['PAGE_SIZE'])
            ->initFromUri();

	    $query  = [
	        'select'        => $this->getSelect(),
            "count_total"   => true,
            'offset'        => $nav->getOffset(),
            'limit'         => $nav->getLimit(),
            'order'         => ['ID' => 'DESC']
        ];

	    $users = Main\UserTable::getList($query);
        $nav->setRecordCount($users->getCount());
        $this->arResult['NAV'] = $nav;

	    return $users;
	}

    /**
     * @param          $user
     * @param Location $location
     * @return mixed
     * @author Pavel Shulaev (https://rover-it.me)
     */
	protected function addLocation($user, Location $location)
    {
        $regIp = $this->getRegIp($user['ID']);
        if (!$regIp)
            return $user;

        try{
            $location->reload($regIp);
        } catch (\Exception $e) {
            return $user;
        }

        $user = $this->addFields($user, $location, 'CITY_FIELDS', Location::FIELD__CITY);
        $user = $this->addFields($user, $location, 'STATE_FIELDS', Location::FIELD__REGION);
        $user = $this->addFields($user, $location, 'COUNTRY_FIELDS', Location::FIELD__COUNTRY_ID);

        return $user;
    }

    /**
     * @return array
     * @author Pavel Shulaev (https://rover-it.me)
     */
	protected function getUsers()
    {
        $users      = $this->getUsersRaw();
        try{
            $location = Location::getInstance();
        } catch (\Exception $e){
            $location = null;
        }

        $result     = [];

        while ($user = $users->fetch())
        {
            if (!$this->checkUserFields($user)
                && ($location instanceof Location))
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

            $this->fields = [];

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
	    $this->update();

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
        $result     = [];
        $countries  =  GetCountryArray();

        foreach ($countries['reference_id'] as $countryPos => $countryId)
            if (isset($countries['reference'][$countryPos]))
                $result[$countryId] = $countries['reference'][$countryPos];

        asort($result);

        $russia = $result[1];
        unset($result[1]);
        $result = [0 => '-', 1 => $russia] + $result;

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
        if (!$locationType)
            return $user;

        if (!isset($this->arParams[$fieldType]))
            return $user;

        foreach ($this->arParams[$fieldType] as $field){
            if (empty($user[$field]))
                $user[$field] = '~~' . $location->getData($locationType);
        }

        return $user;
    }

    /**
     * @param $userId
     * @return null
     * @throws ArgumentNullException
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
		$this->setFrameMode(true);
		if ($this->StartResultCache($this->arParams['CACHE_TIME'])) {
			try {
				$this->setFrameMode(true);
				$this->checkParams();
				$this->getResult();
				$this->includeComponentTemplate();
			} catch (Exception $e) {
				ShowError($e->getMessage());
			}
		}
	}
}