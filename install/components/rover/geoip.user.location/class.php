<?php

/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 02.10.2015
 * Time: 19:12
 *
 * @author Shulaev (pavel.shulaev@gmail.com)
 */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Localization\Loc as Loc;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Statistic\SessionTable;
use Rover\GeoIp\Location;
use Rover\GeoIp\Service;

/**
 * Class RoverSlider
 *
 * @author Pavel Shulaev (https://rover-it.me)
 */
class GeoIpUserLocation extends CBitrixComponent
{
    protected array $fields;

    const INPUT__USER   = 'user';
    const INPUT__SELECT = 'select';
    const INPUT__SUBMIT = 'submit';

    protected static array $allowedCityFields    = [
        'PERSONAL_CITY', 'WORK_CITY'
    ];
    protected static array $allowedStateFields   = [
        'PERSONAL_STATE', 'WORK_STATE'
    ];
    protected static array $allowedCountryFields = [
        'PERSONAL_COUNTRY', 'WORK_COUNTRY'
    ];

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
        if ($params['PAGE_SIZE'] <= 0) {
            $params['PAGE_SIZE'] = 20;
        }

        $params['CITY_FIELDS']    = array_intersect($params['CITY_FIELDS'] ?? [], self::$allowedCityFields);
        $params['STATE_FIELDS']   = array_intersect($params['STATE_FIELDS'] ?? [], self::$allowedStateFields);
        $params['COUNTRY_FIELDS'] = array_intersect($params['COUNTRY_FIELDS'] ?? [], self::$allowedCountryFields);

        return $params;
    }

    /**
     * @throws Main\LoaderException
     * @throws Main\SystemException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected function checkParams()
    {
        global $USER;

        if (!$USER->IsAdmin()) {
            throw new Main\SystemException(Loc::getMessage('rover-geoip__access-denied'));
        }

        if (!Main\Loader::includeModule('rover.geoip')) {
            throw new Main\SystemException(Loc::getMessage('rover-geoip__no-geoip-module'));
        }

        if (!Main\Loader::includeModule('statistic')) {
            throw new Main\SystemException(Loc::getMessage('rover-geoip__no-statistic-module'));
        }
    }

    /**
     * @return array
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected function getSelect(): array
    {
        return array_unique(array_merge(['ID', 'NAME', 'LAST_NAME'], $this->getFields()));
    }

    /**
     * @return Main\DB\Result
     * @throws Main\ArgumentException
     * @throws Main\ObjectPropertyException|Main\SystemException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected function getUsersRaw(): Main\DB\Result
    {
        $nav = new PageNavigation("nav-users");
        $nav->allowAllRecords(false)
            ->setPageSize($this->arParams['PAGE_SIZE'])
            ->initFromUri();

        $query = [
            'select'      => $this->getSelect(),
            "count_total" => true,
            'offset'      => $nav->getOffset(),
            'limit'       => $nav->getLimit(),
            'order'       => ['ID' => 'DESC']
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
     * @throws ArgumentNullException
     * @throws Main\ArgumentException
     * @throws Main\ObjectPropertyException
     * @throws Main\SystemException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected function addLocation($user, Location $location): array
    {
        $regIp = $this->getRegIp($user['ID']);
        if (!$regIp) {
            return $user;
        }

        $user['REG_IP'] = $regIp;

        try {
            $location->reload($regIp);
        } catch (\Exception $e) {
            return $user;
        }

        $user = $this->addFields($user, $location, 'CITY_FIELDS', Service::CITY_NAME);
        $user = $this->addFields($user, $location, 'STATE_FIELDS', Service::REGION_NAME);
        $user = $this->addFields($user, $location, 'COUNTRY_FIELDS', Service::COUNTRY_ID);

        return $user;
    }

    /**
     * @return array
     * @throws ArgumentNullException
     * @throws Main\ArgumentException
     * @throws Main\ObjectPropertyException|Main\SystemException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected function getUsers(): array
    {
        $users    = $this->getUsersRaw();
        $location = Location::getInstance();
        $result   = [];

        while ($user = $users->fetch()) {
            $user     = $this->addLocation($user, $location);
            $result[] = $user;
        }

        return $result;
    }

    /**
     * @return array
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected function getFields(): array
    {
        if (!isset($this->fields)) {
            $fields = array_merge(
                $this->arParams['CITY_FIELDS'] ?? [],
                $this->arParams['STATE_FIELDS'] ?? [],
                $this->arParams['COUNTRY_FIELDS'] ?? []);

            $this->fields = [];

            foreach ($fields as $field) {
                $field = trim($field);
                if (!strlen($field)) {
                    continue;
                }

                $this->fields[] = $field;
            }
        }

        return $this->fields;
    }

    /**
     * @throws Main\SystemException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected function update(): void
    {
        if (!$this->request->getPost(self::INPUT__SUBMIT) || !check_bitrix_sessid()) {
            return;
        }

        $cUser    = new CUser();
        $selected = array_keys($this->request->getPost(self::INPUT__SELECT));
        $users    = $this->request->getPost(self::INPUT__USER);

        foreach ($selected as $userId) {
            $userId = intval($userId);

            if (!isset($users[$userId])) {
                continue;
            }

            foreach ($users[$userId] as $field => $value) {
                if (!in_array($field,
                    array_merge(self::$allowedCityFields, self::$allowedStateFields, self::$allowedCountryFields))) {
                    unset($users[$userId][$field]);
                }
            }

            if (empty($users[$userId])) {
                continue;
            }

            if (!$cUser->Update($userId, $users[$userId])) {
                throw new Main\SystemException($cUser->LAST_ERROR);
            }
        }
        $this->arResult['MESSAGE_CODE'] = 'updated';
    }

    /**
     * @throws Main\SystemException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected function getResult(): void
    {
        $this->arResult['USERS']           = $this->getUsers();
        $this->arResult['FIELDS']          = $this->getSelect();
        $this->arResult['LOCATION_FIELDS'] = $this->getFields();
        $this->arResult['COUNTRIES']       = $this->getCountries();
    }

    /**
     * @return array
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected function getCountries(): array
    {
        $result    = [];
        $countries = GetCountryArray();

        foreach ($countries['reference_id'] as $countryPos => $countryId) {
            if (isset($countries['reference'][$countryPos])) {
                $result[$countryId] = $countries['reference'][$countryPos];
            }
        }

        asort($result);

        $russia = $result[1];
        unset($result[1]);
        $result = [0 => '-', 1 => $russia] + $result;

        return $result;
    }

    /**
     * @param array $user
     * @param Location $location
     * @param string $fieldType
     * @param string $locationType
     * @return mixed
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected function addFields(array $user, Location $location, string $fieldType, string $locationType): array
    {
        $fieldType = trim($fieldType);
        if (!$fieldType) {
            return $user;
        }

        $locationType = trim($locationType);
        if (!strlen($locationType)) {
            return $user;
        }

        if (!isset($this->arParams[$fieldType])) {
            return $user;
        }

        foreach ($this->arParams[$fieldType] as $field) {
            $user['~' . $field] = $location->getField($locationType);
        }

        return $user;
    }

    /**
     * @param $userId
     * @return string|null
     * @throws ArgumentNullException
     * @throws Main\ArgumentException
     * @throws Main\ObjectPropertyException
     * @throws Main\SystemException
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected function getRegIp(int $userId): ?string
    {
        if (!$userId) {
            throw new ArgumentNullException('userId');
        }

        if (!Main\Loader::includeModule('statistic')) {
            return null;
        }

        $session = SessionTable::getList([
            'select' => ['IP_LAST'],
            'filter' => ['=USER_ID' => $userId],
            'order'  => ['DATE_FIRST' => 'ASC'],
            'limit'  => 1,
        ])->fetch();

        return $session['IP_LAST'] ?? null;
    }

    /**
     * @param $user
     * @return bool
     * @author Pavel Shulaev (https://rover-it.me)
     */
    protected function checkUserFields($user)
    {
        $fields = $this->getFields();

        foreach ($fields as $field) {
            if (!isset($user[$field])) {
                return false;
            }
        }

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
        } catch (Throwable $e) {
            ShowError($e->getMessage());
        }
    }
}