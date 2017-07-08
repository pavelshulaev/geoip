<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);


if (empty($arResult['USERS'])){
    ShowError('no users');
    return;
}
?><section>
    <?
    $APPLICATION->IncludeComponent(
        "bitrix:main.pagenavigation",
        "",
        array(
            "NAV_OBJECT" => $arResult['NAV'],
        //    "SEF_MODE" => "Y",
        ),
        false
    );?>
    <form class="form" method="post">
        <input class="btn btn-primary"
               type="submit"
               name="<?=GeoIpUserLocation::INPUT__SUBMIT?>"
               value="<?=Loc::getMessage('rover-gi-ul__update')?>">
        <table class="table">
            <thead>
                <tr>
                    <th></th>
                    <?php foreach ($arResult['FIELDS'] as $field): ?>
                        <th><?=$field?>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($arResult['USERS'] as $user): ?>
                <tr>
                    <td><input class="update-marker" type="checkbox" name="<?=GeoIpUserLocation::INPUT__SELECT?>[<?=$user['ID']?>]"></td>
                    <?php foreach ($arResult['FIELDS'] as $field):

                        $value = isset($user[$field]) ? trim($user[$field]) : '';
                        $class = '';

                        if (strlen($value) && strpos($value, '~~') === 0) {
                            $class = 'has-success';
                            $value = substr($value, 2);
                        }

                        ?><td class="<?=$class?>"><?php

                        if (in_array($field, $arResult['LOCATION_FIELDS'])):
                            if (in_array($field, $arParams['COUNTRY_FIELDS'])):?>
                                <select
                                        name="<?=GeoIpUserLocation::INPUT__USER?>[<?=$user['ID']?>][<?=$field?>]"
                                        class="form-control">
                                    <?php foreach ($arResult['COUNTRIES'] as $countryId => $countryName): ?>
                                        <option value="<?=$countryId?>"
                                                <?=$countryId==$value?'selected="selected"':''?>><?=$countryName?></option>
                                    <?php endforeach; ?>
                                </select>
                             <?php else:
                            ?><input
                                class="form-control"
                                value="<?=$value?>"
                                name="<?=GeoIpUserLocation::INPUT__USER?>[<?=$user['ID']?>][<?=$field?>]"
                                type="text"><?php

                                endif;

                        else:?>
                            <?=$value?>
                        <?php endif; ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <input class="btn btn-primary"
               type="submit"
               name="<?=GeoIpUserLocation::INPUT__SUBMIT?>"
               value="<?=Loc::getMessage('rover-gi-ul__update')?>">
    </form>
    <?
    $APPLICATION->IncludeComponent(
        "bitrix:main.pagenavigation",
        "",
        array(
            "NAV_OBJECT" => $arResult['NAV'],
            //    "SEF_MODE" => "Y",
        ),
        false
    );?>
</section>