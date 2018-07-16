# Модуль для Битрикс «GeoIp Api»

* [Описание](#Описание)
* [Api](#api)
* [Пример использования](#Пример-использования)
* [Требования](#Требования)
* [Контакты](#Контакты)

## Описание
Модуль предоставляет api для определения местоположения по ip-адресу. По-умолчанию местоположение определяется по текущему ip.

### В местоположение входят

* название города;
* iso-код страны
* id страны в CMS 1С Битрикс (соответствует id стран, возвращаемых функцией [GetCountryArray](https://dev.1c-bitrix.ru/api_help/main/functions/other/getcountryarray.php))
* название страны на языке сайта;
* название региона;
* iso-код региона;
* название района;
* ширина и долгота;
* диапазон ip-адресов.

В зависимости от выбранной службы, значения некоторых полей могут <b>отсутствовать</b> либо <b>отображаться на английском языке</b>.

### Службы определения местоположения

В обычном режиме решение предоставляет данные из первой службы, корректно вернувшей данные. Службы вызываются в порядке, указанном ниже:
* ipgeobase.ru (IpGeoBase);
* sypexgeo.net (Sypex);
* (деактивирован с версии 1.4.5, т.к. у сервиса было отключено свободное api, при вызове сервиса теперь возникает ошибка) freegeoip.net (FreeGeoIp).

В случае необходимости, можно явно указать необходимую службу в 3м параметре `Location::getInstance()`.

### Кеширование
Для уменьшения количества запросов, гео-инофрмация по последему ip сохраняется в куках.

### Маркетплейс
Модуль доступен на [Маркетплейсе Битрикса](http://marketplace.1c-bitrix.ru/solutions/rover.geoip/).

## Api
### Класс `\Rover\GeoIp\Location`

#### `public static getInstance($ip = null, $charset = self::CHARSET__AUTO, $service = '', $language = LANGUAGE_ID)`
Возвращает объект `\Rover\GeoIp\Location` для переднного ip-адреса. 
* `$ip` - ip-адрес, по умолчанию используется текущий;
* `$charset` - кодировка, по умолчания кодировка сайта;
* `$service` - предпочитаемый сервис. По умолчанию сервисы вызываются в порядке, описанном в разделе [Службы определения местоположения](#Службы-определения-местоположения) и используется первый, вернувший результат.
* `$language` - предпочитаемый язык ответа, по умолчанию равеня текущему языку сайта. На данный момент работает только с сервисом Sypex.

#### `public reload($ip = null)`
Метод позволяет загрузить/перезагрузить данные напрямую из сервисов геопозиционирования, минуя кеш.
* `$ip` - ip-адрес, для которого перезагружаем данные. По умолчанию используется текущий;

> Благодаря этому методу, можно несколько раз использовать объект `\Rover\GeoIp\Location` для определения местоположения по разным ip-адресам, не создавая каждый раз новый (см. [пример использования](#Пример-использования)).

#### `public static getCurIp()`
Возвращает текущий ip-адрес.

#### `public getData()`
Возвращает ассоциативный массив со всеми данными, которые удалось получить по ip.

	[
		'ip'            => 'xxx.xxx.xxx.xxx',
		'inetnum'       => '...',
		'country_code'  => '...',
		'country_name   => '...',
		'country_id     => '...',
		'city_name'     => '...',
		'region_name'   => '...',
		'district'      => '...',
		'lat'           => '...',
		'lng'           => '...'
	]	
	
#### `public getField($field)`	
Возвращает значение поля массива из метода `public getData()`.

    $location = \Rover\GeoIp\Location::getInstance();
    $location->getField('region_name');
	
#### `public getCityName()`
Возвращает название города.	

#### `public getCountryCode()`
Возвращает iso-код страны.	

#### `public getCountryId()`
Возвращает id страны в Битриксе (соответствует id стран, возвращаемых функцией [GetCountryArray](https://dev.1c-bitrix.ru/api_help/main/functions/other/getcountryarray.php)). Для корректной работы необходимо, чтобы результат `public getCountryCode()` был не пустым.

#### `public getCountryName()`
Возвращает название страны на текущем языке сайта. Для корректной работы необходимо, чтобы результат `public getCountryId()` был не пустым.

#### `public getRegionName()`
Возвращает название региона.	

#### `public getRegionCode()`
Возвращает iso-код региона.	

#### `public getDistrict()`
Возвращает название района.

#### `public getLat()`
Возвращает широту.	

#### `public getLng()`
Возвращает долготу.					

#### `public getInetnum()`
Возвращает диапазон адресов, в который входит переданный ip.					

#### `public getService()`
Возвращает название geoip-сервиса, с помощью которого были получены данные
	
## Пример использования

	use Bitrix\Main\Loader,
        Rover\GeoIp\Location;

    if (Loader::includeModule('rover.geoip')){
        try{
            echo 'ваш ip: ' . Location::getCurIp() . '<br><br>'; // текущий ip
            
            $location = Location::getInstance('5.255.255.88'); // yandex.ru
            
            echo 'ip: '                 . $location->getIp() . '<br>';          // 5.255.255.88
            echo 'город: '              . $location->getCityName() . '<br>';        // Москва
            echo 'iso-код страны: '     . $location->getCountryCode() . '<br>';     // RU
            echo 'название страны: '    . $location->getCountryName() . '<br>'; // Россия
            echo 'id страны в Битриксе: '    . $location->getCountryId() . '<br>'; // 1
            echo 'регион: '             . $location->getRegionName() . '<br>';      // Москва
            echo 'iso-код региона: '    . $location->getRegionCode() . '<br>';      // 
            echo 'округ: '              . $location->getDistrict() . '<br>';    // Центральный федеральный округ
            echo 'широта: '             . $location->getLat() . '<br>';         // 55.755787
            echo 'долгота: '            . $location->getLng() . '<br>';         // 37.617634
            echo 'диапазон адресов: '   . $location->getInetnum() . '<br>';     // 5.255.252.0 - 5.255.255.255
            echo 'сервис: '             . $location->getService() . '<br><br>';     // IpGeoBase
            
            $location->setLanguage('en');
            $location->reload('173.194.222.94'); // google.ru
    
            echo 'ip: '                 . $location->getIp() . '<br>';          // 173.194.222.94
            echo 'город: '              . $location->getCityName() . '<br>';        // Mountain View
            echo 'iso-код страны: '     . $location->getCountryCode() . '<br>';     // US
            echo 'название страны: '    . $location->getCountryName() . '<br>'; // USA
            echo 'id страны в Битриксе: '    . $location->getCountryId() . '<br>'; // 122
            echo 'регион: '             . $location->getRegionName() . '<br>';      // California
            echo 'iso-код региона: '    . $location->getRegionCode() . '<br>';      //
            echo 'округ: '              . $location->getDistrict() . '<br>';    // US-CA
            echo 'широта: '             . $location->getLat() . '<br>';         // 37.38605
            echo 'долгота: '            . $location->getLng() . '<br>';         // -122.08385
            echo 'диапазон адресов: '   . $location->getInetnum() . '<br>';     //
            echo 'сервис: '             . $location->getService() . '<br>';     // Sypex
            
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
	} else 
        echo 'Модуль GeoIp Api не установлен';
		
## Компоненты

### Указатель местоположения пользователей (rover:geoip.user.location)
Позволяет установить местоположение для пользователей на основе ip адреса, с которого они впервые зашли на сайт. Для работы необходим установленный модуль «Веб-аналитика».

Определившиеся значения подсвечиваются зеленым цветом. Чтобы обновить значение, необходимо выделить галочкой соответствующую строку и нажать «Обновить».

В визуальном редакторе компонент находится по адресу `Компоненты Rover -> Служебные -> Указатель местоположения пользователей`.

#### Параметры
* `PAGE_SIZE` - Количество пользователей на одной странице
* `CITY_FIELDS` - Поля пользователя, куда следует внести информацию о городе
* `STATE_FIELDS` - Поля пользователя, куда следует внести информацию о регионе
* `COUNTRY_FIELDS` - Поля пользователя, куда следует внести информацию о стране

## Требования	
* php версии 5.3 или выше;
* установленная на хостинге библиотека CURL;
* модуль «Веб-аналитика» (для работы компонента rover:geoip.user.location).

## Контакты
По всем вопросам вы можете связаться со мной по email: rover.webdev@gmail.com, либо через форму на сайте https://rover-it.me.

## Пожертвования
Если решение оказалось вам полезным, вы можете оставить пожертование

[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=V9Y7ZLDB5X8Z6)