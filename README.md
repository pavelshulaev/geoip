# Модуль для Битрикс «GeoIp Api»

* [Описание](#Описание)
* [Api](#api)
* [Пример использования](#Пример-использования)
* [Расширение списком служб](#Расширение-списком-служб)
* [История изменений](#История-изменений)
* [Требования](#Требования)
* [Контакты](#Контакты)

## Описание

Модуль предоставляет api для определения местоположения по ip-адресу. По-умолчанию местоположение определяется по
текущему ip.

### В местоположение входят

* название города;
* iso-код страны
* id страны в CMS 1С Битрикс (соответствует id стран, возвращаемых
  функцией [GetCountryArray](https://dev.1c-bitrix.ru/api_help/main/functions/other/getcountryarray.php))
* название страны на языке сайта;
* название региона;
* iso-код региона;
* название района;
* ширина и долгота;
* диапазон ip-адресов.

В зависимости от выбранной службы, значения некоторых полей могут <b>отсутствовать</b> либо <b>отображаться на
английском языке</b>. Например, встроенная служба Sypex не возвращает «название района» и «диапазон ip-адресов» — эти
поля можно получить от кастомных служб, подключённых через [событие `onBuildServiceList`](#Расширение-списком-служб),
если такая служба их заполняет.

### Службы определения местоположения

В обычном режиме решение предоставляет данные из первой службы, корректно вернувшей данные. Список служб и их порядок
собираются через событие `onBuildServiceList` (см. [Расширение списком служб](#Расширение-списком-служб)). В поставке —
одна встроенная служба:

* sypexgeo.net (Sypex) — активна по умолчанию.

Службы ipgeobase.ru и freegeoip.net удалены из модуля (см. [CHANGELOG](CHANGELOG.md)), т.к. оба сервиса прекратили
работу и не предоставляют публичное api. При необходимости их можно подключить как сторонние службы через событие
`onBuildServiceList`.

В случае необходимости, можно явно указать необходимую службу в 3м параметре `Location::getInstance()`.

### Кеширование

Для уменьшения количества запросов, гео-информация по последнему ip сохраняется в куках.

### Маркетплейс

Модуль доступен на [Маркетплейсе Битрикса](https://marketplace.1c-bitrix.ru/solutions/rover.geoip/). Исходный код — на
[GitHub](https://github.com/pavelshulaev/geoip).

## Api

### `\Rover\GeoIp\Location`

#### Получение объекта `\Rover\GeoIp\Location`

    $location = \Rover\GeoIp\Location::getInstance($ip = '', $charset = LANG_CHARSET, $service = '', $language = LANGUAGE_ID);

* `$ip` - ip-адрес, по умолчанию используется текущий;
* `$charset` - кодировка, по умолчанию кодировка сайта (`LANG_CHARSET`);
* `$service` - предпочитаемый сервис. По умолчанию сервисы вызываются в порядке, описанном в
  разделе [Службы определения местоположения](#Службы-определения-местоположения) и используется первый, вернувший
  результат.
* `$language` - предпочитаемый язык ответа, по умолчанию равен текущему языку сайта. На данный момент работает только с
  сервисом Sypex.

#### Reload

    $location->reload($ip = '');

Метод позволяет загрузить/перезагрузить данные напрямую из сервисов геопозиционирования, минуя кеш.

* `$ip` - ip-адрес, для которого перезагружаем данные. По умолчанию используется текущий;

> Благодаря этому методу, можно несколько раз использовать объект `\Rover\GeoIp\Location` для определения местоположения
> по разным ip-адресам, не создавая каждый раз новый (см. [пример использования](#Пример-использования)).

#### Получение данных

    use \Rover\GeoIp\Location;

    $location = Location::getInstance();
    $location->isSuccess();             // Флаг успешного получения данных
    $location->getError();              // ошибки, возникшие при получении данных
    $location->getCurIp();              // текущий ip-адрес.
    $location->getData();               // Возвращает ассоциативный массив вида

	[
		'city_name'     => 'Москва',
		'lat'           => '55.75222',
		'lng'           => '37.61556',
		'region_code'   => 'RU-MOW',
		'region_name'   => 'Москва',
		'country_code'  => 'RU',
		'country_name'  => 'Россия',
		'country_id'    => 1,
		'service_name'  => 'Sypex',
		'ip'            => '5.255.255.88'
	]	

Реальный набор ключей зависит от службы. Встроенная служба Sypex ключи `district` и `inetnum` не возвращает вовсе (не
пустой строкой, а отсутствием ключа) — соответствующие геттеры вернут `null`.

    $location->getField('region_name'); // значение поля массива из метода getData
	$location->getCityName();           // название города.	
	$location->getCountryCode();        // iso-код страны.
	$location->getCountryId();          // id страны в Битриксе (соответствует id стран, возвращаемых функцией GetCountryArray (https://dev.1c-bitrix.ru/api_help/main/functions/other/getcountryarray.php)). Для корректной работы необходимо, чтобы результат getCountryCode() был не пустым.
	$location->getCountryName();        // название страны на текущем языке сайта. Для корректной работы необходимо, чтобы результат getCountryId() был не пустым.
	$location->getRegionName();         // название региона.
	$location->getRegionCode();         // iso-код региона.	
	$location->getDistrict();           // название района (для Sypex всегда null, см. выше).
	$location->getLat();                // широта
	$location->getLng();                // долгота
	$location->getInetnum();            // диапазон адресов, в который входит переданный ip (для Sypex всегда null, см. выше).
	$location->getServiceName();        // название geoip-сервиса, с помощью которого были получены данные

## Пример использования

	use Bitrix\Main\Loader,
        Rover\GeoIp\Location;
    
    if (Loader::includeModule('rover.geoip')){
        try{
            echo 'ваш ip: ' . Location::getCurIp() . '<br><br>'; // текущий ip
    
            $location = Location::getInstance('195.19.132.64', LANG_CHARSET, 'Sypex'); // yandex.ru
            if ($location->isSuccess())
            {
                echo 'ip: '                 . $location->getIp() . '<br>';          // 5.255.255.88
                echo 'город: '              . $location->getCityName() . '<br>';        // Москва
                echo 'iso-код страны: '     . $location->getCountryCode() . '<br>';     // RU
                echo 'название страны: '    . $location->getCountryName() . '<br>'; // Россия
                echo 'id страны в Битриксе: '    . $location->getCountryId() . '<br>'; // 1
                echo 'регион: '             . $location->getRegionName() . '<br>';      // Москва
                echo 'iso-код региона: '    . $location->getRegionCode() . '<br>';      // RU-MOW
                echo 'округ: '              . $location->getDistrict() . '<br>';    // null (Sypex не заполняет)
                echo 'широта: '             . $location->getLat() . '<br>';         // 55.75222
                echo 'долгота: '            . $location->getLng() . '<br>';         // 37.61556
                echo 'диапазон адресов: '   . $location->getInetnum() . '<br>';     // null (Sypex не заполняет)
                echo 'сервис: '             . $location->getServiceName() . '<br><br>';     // Sypex
            } else {
                echo 'ошибка: '             . $location->getError() . '<br><br>';
            }
    
            $location->setLanguage('en');
            $location->reload('173.194.222.94'); 
    
            if ($location->isSuccess())
            {
                echo 'ip: '                 . $location->getIp() . '<br>';          // 173.194.222.94
                echo 'город: '              . $location->getCityName() . '<br>';        // Ashburn
                echo 'iso-код страны: '     . $location->getCountryCode() . '<br>';     // US
                echo 'название страны: '    . $location->getCountryName() . '<br>'; // USA
                echo 'id страны в Битриксе: '    . $location->getCountryId() . '<br>'; // 122
                echo 'регион: '             . $location->getRegionName() . '<br>';      // Virginia
                echo 'iso-код региона: '    . $location->getRegionCode() . '<br>';      // US-VA
                echo 'округ: '              . $location->getDistrict() . '<br>';    // null (Sypex не заполняет)
                echo 'широта: '             . $location->getLat() . '<br>';         // 39.04372
                echo 'долгота: '            . $location->getLng() . '<br>';         // -77.48749
                echo 'диапазон адресов: '   . $location->getInetnum() . '<br>';     // null (Sypex не заполняет)
                echo 'сервис: '             . $location->getServiceName() . '<br>';     // Sypex
            } else {
                echo 'ошибка: '             . $location->getError() . '<br><br>';
            }
    
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    } else
        echo 'Модуль GeoIp Api не установлен';

## Настройки

### Указание сервера для Sypex

    \Bitrix\Main\Config\Option::set('rover.geoip', 'sypex-server', 'ru.sxgeo.city');

Список всех серверов https://sypexgeo.net/ru/api/

### Максимальное время ожидания ответа от geoip-службы

    \Bitrix\Main\Config\Option::set('rover.geoip', 'curl-timeout', 200);

Значение в миллисекундах, по умолчанию — 200.

## Компоненты

### Указатель местоположения пользователей (rover:geoip.user.location)

Позволяет установить местоположение для пользователей на основе ip адреса, с которого они впервые зашли на сайт. Для
работы необходим установленный модуль «Веб-аналитика».

Компонент доступен только администраторам — при обращении от имени любого другого пользователя (`$USER->IsAdmin() === false`)
выбрасывается исключение и выводится сообщение о нехватке прав.

Определившиеся значения подсвечиваются зеленым цветом. Чтобы обновить значение, необходимо выделить галочкой
соответствующую строку и нажать «Обновить».

В визуальном редакторе компонент находится по адресу
`Компоненты Rover -> GeoIp Api -> Указатель местоположения пользователей`.

#### Параметры

* `PAGE_SIZE` - Количество пользователей на одной странице
* `CITY_FIELDS` - Поля пользователя, куда следует внести информацию о городе
* `STATE_FIELDS` - Поля пользователя, куда следует внести информацию о регионе
* `COUNTRY_FIELDS` - Поля пользователя, куда следует внести информацию о стране

## Расширение списком служб

Список geoip-служб собирается через событие `onBuildServiceList` модуля `rover.geoip` — тем же способом подключена и
встроенная служба Sypex. Чтобы добавить свою службу (например, из другого модуля), зарегистрируйте обработчик события и
верните из него полные имена классов, наследующих `\Rover\GeoIp\Service`:

    use Bitrix\Main\Event;
    use Bitrix\Main\EventResult;
    use Bitrix\Main\EventManager;

    EventManager::getInstance()->addEventHandler(
        'rover.geoip',
        'onBuildServiceList',
        function (Event $event) {
            return new EventResult(EventResult::SUCCESS, [
                'services' => [
                    \My\Module\Service\MyGeoService::class,
                ],
            ], 'my.module');
        }
    );

* Класс службы должен наследовать `\Rover\GeoIp\Service` и реализовывать `isActive()`, `parse()`, `getManifest()` — так
  же, как встроенная `\Rover\GeoIp\Service\Sypex`.
* Порядок вызова служб определяется полем `sort` в `getManifest()` каждой службы, а не порядком регистрации
  обработчиков.
* Классы, которых нет или которые не наследуют `Service`, молча пропускаются — ошибка в одном обработчике не ломает
  определение местоположения целиком.
* Регистрировать обработчик нужно до вызова `Location::getInstance()`/`getData()` — обычно это делают в `init.php`
  проекта или в `include.php`/`DoInstall()` своего модуля.

## История изменений

Полный список версий и изменений — в [CHANGELOG.md](CHANGELOG.md).

## Требования

* php версии 8.1 или выше (совместимость дополнительно проверена на php 8.2);
* установленная на хостинге библиотека CURL;
* модуль «Веб-аналитика» (для работы компонента rover:geoip.user.location).

## Контакты

По всем вопросам вы можете связаться со мной по email: rover.webdev@gmail.com, либо через форму на
сайте https://rover-it.me.

## Пожертвования

> Если решение оказалось вам полезным, вы можете оставить пожертвование

<a href="https://pay.cloudtips.ru/p/dc96a2b8" target="_blank">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="https://rover-it.me/marketplace/icon-donate-dark.svg">
    <source media="(prefers-color-scheme: light)" srcset="https://rover-it.me/marketplace/icon-donate-light.svg">
    <img src="https://rover-it.me/marketplace/icon-donate-light.svg" width="18" alt="Donate">
  </picture>
  Сделать пожертвование
</a>
