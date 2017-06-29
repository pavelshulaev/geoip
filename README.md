# Модуль для Битрикс «GeoIp Api»

[Api](#api)

[Пример использования](#Пример-использования)

[Требования](#Требования)

[Контакты](#Контакты)

## Описание
Модуль предоставляет api для определения местоположения по ip-адресу. Если ip-адрес не указан явно, то местоположение определяется по текущему ip пользователя.

В местоположение входят:

* город;
* код страны;
* название страны на русском языке;
* регион;
* район;
* ширина и долгота;
* диапазон ip-адресов, в который входит переданный.

Модуль доступен на [Маркетплейсе Битрикса](http://marketplace.1c-bitrix.ru/solutions/rover.geoip/).

## Api
### `\Rover\GeoIp\Location`
#### `public static \Rover\GeoIp\Location::getInstance($ip = null, $charset = self::CHARSET__UTF_8)`
Метод возвращает объект `\Rover\GeoIp\Location` для переднного ip-адреса. Если ip-адрес не передан, то объект возвращается для текуего ip пользователя.

Вторым параметром можно передать кодировку возвращаемых данных. Если на не передана, то по умолчанию берется utf-8.
#### `public static getCurIp()`
Возвращает ip-адрес текущего пользователя.
#### `public getData($field = null)`
Возвращает ассоциативный массив со всеми данными, которые удалось получить по ip. Если данные получить не удалось, в массиве возвращается только один элемент с ключем `ip`, с котором находится переданный ip.

Есть возможность получить значение только одного поля. Для этого надо передать его имя в параметре.

	[
		'ip'            => 'xxx.xxx.xxx.xxx',
		'inetnum'       => '...',
		'country'       => '...',
		'city'          => '...',
		'region'        => '...',
		'district'      => '...',
		'lat'           => '...',
		'lng'           => '...'
	]	
	
#### `public getCity()`
Возвращает город, либо `null`, если не удалось получить результат.	
#### `public getCountry()`
Возвращает код страны, либо `null`, если не удалось получить результат.	
#### `public getCountryName($lang = LANGUAGE_ID)`
Возвращает название страны для переданного кода языка (по умолчанию - текщуий язык сайта), либо `null`, если не удалось получить результат. По умолчанию в модуле доступны названия только на русском языке.
#### `public getCountryId()`
Возвращает id страны в Битриксе (если удалось определить).
#### `public getRegion()`
Возвращает регион, либо `null`, если не удалось получить результат.	
#### `public getDistrict()`
Возвращает район, либо `null`, если не удалось получить результат.
#### `public getLat()`
Возвращает широту, либо `null`, если не удалось получить результат.	
#### `public getLng()`
Возвращает долготу, либо `null`, если не удалось получить результат.					
#### `public getInetnum()`
Возвращает диапазон адресов, в скоторый входит переданный ip, либо `null`, если не удалось получить результат.	
## Пример использования
### для сайта в кодировке utf-8

	use Bitrix\Main\Loader,
        Rover\GeoIp\Location;

    if (Loader::includeModule('rover.geoip')){
        try{
            echo 'ваш ip: ' . Location::getCurIp() . '<br><br>'; // текущий ip
            
            $location = Location::getInstance('5.255.255.88'); // yandex.ru
            
            echo 'ip: '                 . $location->getIp() . '<br>';          // 5.255.255.88
            echo 'город: '              . $location->getCity() . '<br>';        // Москва
            echo 'код страны: '         . $location->getCountry() . '<br>';     // RU
            echo 'название страны: '    . $location->getCountryName() . '<br>'; // Россия
            echo 'код страны в Битриксе: '    . $location->getCountryId() . '<br>'; // 1
            echo 'регион: '             . $location->getRegion() . '<br>';      // Москва
            echo 'округ: '              . $location->getDistrict() . '<br>';    // Центральный федеральный округ
            echo 'широта: '             . $location->getLat() . '<br>';         // 55.755787
            echo 'долгота: '            . $location->getLng() . '<br>';         // 37.617634
            echo 'диапазон адресов: '   . $location->getInetnum() . '<br>';     // 5.255.252.0 - 5.255.255.255
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
	} else 
        echo 'Модуль GeoIp Api не установлен';
### для сайта в кодировке windows-1251

    use Bitrix\Main\Loader,
        Rover\GeoIp\Location,
        Rover\GeoIp\Service\Base;
	
    if (Loader::includeModule('rover.geoip')){
        try{
            echo 'ваш ip: ' . Location::getCurIp() . '<br><br>'; // текущий ip
            
            $location = Location::getInstance('5.255.255.88', Base::CHARSET__WINDOWS_1251); // yandex.ru
            
            echo 'ip: '                 . $location->getIp() . '<br>';          // 5.255.255.88
            echo 'город: '              . $location->getCity() . '<br>';        // Москва
            echo 'код страны: '         . $location->getCountry() . '<br>';     // RU
            echo 'название страны: '    . $location->getCountryName() . '<br>'; // Россия
            echo 'регион: '             . $location->getRegion() . '<br>';      // Москва
            echo 'округ: '              . $location->getDistrict() . '<br>';    // Центральный федеральный округ
            echo 'широта: '             . $location->getLat() . '<br>';         // 55.755787
            echo 'долгота: '            . $location->getLng() . '<br>';         // 37.617634
            echo 'диапазон адресов: '   . $location->getInetnum() . '<br>';     // 5.255.252.0 - 5.255.255.255
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
	} else 
        echo 'Модуль GeoIp Api не установлен';
		
		
## Требования	
Для работы «GeoIp Api» необходим установленный на хостинге php версии 5.4 или выше и модуль CURL.
## Контакты
По всем вопросам вы можете связаться со мной по email: rover.webdev@gmail.com, либо через форму на сайте http://rover-it.me.
