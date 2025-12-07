<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);
$module_id = GetModuleID(__FILE__);

Loader::includeModule('main');
Loader::includeModule('highloadblock');
Loader::includeModule('iblock');
Loader::includeModule($module_id);

// Функция для получения значения с приоритетом из запроса
function getSettingValue($module_id, $key, $default = "") {
    if (isset($_REQUEST[$key])) {
        return $_REQUEST[$key];
    }
    return Option::get($module_id, $key, $default);
}

// Обработка сохранения настроек
if (($_REQUEST['Update'] || $_REQUEST['Apply']) && check_bitrix_sessid()) {
    // Сохраняем настройки
    Option::set($module_id, "user_group_id", $_REQUEST['user_group_id']);
    Option::set($module_id, "iblock_catalog_section_id", $_REQUEST['iblock_catalog_section_id']);
    Option::set($module_id, "iblock_catalog_id", $_REQUEST['iblock_catalog_id']);
    Option::set($module_id, "auto_hlblock_id", $_REQUEST['auto_hlblock_id']);
    Option::set($module_id, "iblock_purchase_requests_id", $_REQUEST['iblock_purchase_requests_id']);
    Option::set($module_id, "purchase_requests_parts_field", $_REQUEST['purchase_requests_parts_field']);
}

// Получаем список инфоблоков
$iblockList = array();
$res = CIBlock::GetList(array("SORT" => "ASC"), array("ACTIVE" => "Y"));
while ($iblock = $res->Fetch()) {
    $iblockList[$iblock["ID"]] = "[{$iblock["ID"]}] {$iblock["NAME"]}";
}

// Получаем список Highload блоков
$highloadList = array();
if (Loader::includeModule('highloadblock')) {
    $hlblocks = Bitrix\Highloadblock\HighloadBlockTable::getList();
    while ($hlblock = $hlblocks->fetch()) {
        $highloadList[$hlblock["ID"]] = "[{$hlblock["ID"]}] {$hlblock["NAME"]}";
    }
}

// Получаем список групп пользователей
$userGroupsList = array();
$groupsRes = CGroup::GetList($by = "c_sort", $order = "asc", array("ACTIVE" => "Y"));
while ($group = $groupsRes->Fetch()) {
    $userGroupsList[$group["ID"]] = "[{$group["ID"]}] {$group["NAME"]}";
}

// Получаем выбранные значения ИЗ ЗАПРОСА (а не из настроек)
$selectedCatalogIblockId = getSettingValue($module_id, "iblock_catalog_id");
$selectedPurchaseIblockId = getSettingValue($module_id, "iblock_purchase_requests_id");

// Получаем список разделов для выбранного инфоблока каталога
$catalogSectionsList = array();
if ($selectedCatalogIblockId && Loader::includeModule('iblock')) {
    $sectionsRes = CIBlockSection::GetList(
        array("LEFT_MARGIN" => "ASC"),
        array("IBLOCK_ID" => $selectedCatalogIblockId, "ACTIVE" => "Y"),
        false,
        array("ID", "NAME", "DEPTH_LEVEL")
    );
    while ($section = $sectionsRes->Fetch()) {
        $prefix = str_repeat(" . ", $section['DEPTH_LEVEL'] - 1);
        $catalogSectionsList[$section['ID']] = $prefix . "[{$section['ID']}] {$section['NAME']}";
    }
}

// Получаем список полей для выбранного инфоблока заявок закупок
$purchaseIblockFields = array();
if ($selectedPurchaseIblockId && Loader::includeModule('iblock')) {
    $properties = CIBlockProperty::GetList(array("sort" => "asc"), array("IBLOCK_ID" => $selectedPurchaseIblockId, "ACTIVE" => "Y"));
    while ($prop = $properties->Fetch()) {
        // Показываем только поля типа "список" и "привязка к элементам"
        if (in_array($prop['PROPERTY_TYPE'], array('L', 'E', 'S'))) {
            $purchaseIblockFields[$prop['CODE']] = "[{$prop['CODE']}] {$prop['NAME']}";
        }
    }
}

$aTabs = array(
    array(
        'DIV' => 'garage_general',
        'TAB' => Loc::getMessage('GARAGE_TAB_TITLE_GENERAL_SETTINGS'),
        'ICON' => '',
        'TITLE' => Loc::getMessage('GARAGE_TAB_TITLE_GENERAL_SETTINGS')
    )
);

$tabControl = new CAdminTabControl("tabControl", $aTabs);

$tabControl->Begin();
?>
<form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($module_id)?>&lang=<?=LANGUAGE_ID?>">
    <?=bitrix_sessid_post()?>
    
    <?php $tabControl->BeginNextTab(); // Общие настройки ?>
    
    <tr>
        <td width="40%"><?=Loc::getMessage('GARAGE_USER_GROUP_ID')?>:</td>
        <td width="60%">
            <select name="user_group_id">
                <option value=""><?=Loc::getMessage('GARAGE_SELECT_EMPTY')?></option>
                <?php foreach ($userGroupsList as $id => $name): ?>
                    <option value="<?=$id?>" <?=getSettingValue($module_id, "user_group_id") == $id ? 'selected' : ''?>>
                        <?=htmlspecialcharsbx($name)?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    
    <tr>
        <td width="40%"><?=Loc::getMessage('GARAGE_IBLOCK_CATALOG_ID')?>:</td>
        <td width="60%">
            <select name="iblock_catalog_id" id="iblock_catalog_id" onchange="this.form.submit()">
                <option value=""><?=Loc::getMessage('GARAGE_SELECT_EMPTY')?></option>
                <?php foreach ($iblockList as $id => $name): ?>
                    <option value="<?=$id?>" <?=getSettingValue($module_id, "iblock_catalog_id") == $id ? 'selected' : ''?>>
                        <?=htmlspecialcharsbx($name)?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    
    <?php if (!empty($catalogSectionsList)): ?>
    <tr>
        <td width="40%"><?=Loc::getMessage('GARAGE_IBLOCK_CATALOG_SECTION_ID')?>:</td>
        <td width="60%">
            <select name="iblock_catalog_section_id">
                <option value=""><?=Loc::getMessage('GARAGE_SELECT_EMPTY')?></option>
                <?php foreach ($catalogSectionsList as $id => $name): ?>
                    <option value="<?=$id?>" <?=getSettingValue($module_id, "iblock_catalog_section_id") == $id ? 'selected' : ''?>>
                        <?=htmlspecialcharsbx($name)?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <?php endif; ?>
    
    <tr>
        <td width="40%"><?=Loc::getMessage('GARAGE_AUTO_HLBLOCK_ID')?>:</td>
        <td width="60%">
            <select name="auto_hlblock_id">
                <option value=""><?=Loc::getMessage('GARAGE_SELECT_EMPTY')?></option>
                <?php foreach ($highloadList as $id => $name): ?>
                    <option value="<?=$id?>" <?=getSettingValue($module_id, "auto_hlblock_id") == $id ? 'selected' : ''?>>
                        <?=htmlspecialcharsbx($name)?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    
    <tr>
        <td width="40%"><?=Loc::getMessage('GARAGE_IBLOCK_PURCHASE_REQUESTS_ID')?>:</td>
        <td width="60%">
            <select name="iblock_purchase_requests_id" id="purchase_requests_iblock" onchange="this.form.submit()">
                <option value=""><?=Loc::getMessage('GARAGE_SELECT_EMPTY')?></option>
                <?php foreach ($iblockList as $id => $name): ?>
                    <option value="<?=$id?>" <?=getSettingValue($module_id, "iblock_purchase_requests_id") == $id ? 'selected' : ''?>>
                        <?=htmlspecialcharsbx($name)?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    
    <?php if ($selectedPurchaseIblockId && !empty($purchaseIblockFields)): ?>
    <tr>
        <td width="40%"><?=Loc::getMessage('GARAGE_PURCHASE_REQUESTS_PARTS_FIELD')?>:</td>
        <td width="60%">
            <select name="purchase_requests_parts_field">
                <option value=""><?=Loc::getMessage('GARAGE_SELECT_EMPTY')?></option>
                <?php foreach ($purchaseIblockFields as $code => $name): ?>
                    <option value="<?=$code?>" <?=getSettingValue($module_id, "purchase_requests_parts_field") == $code ? 'selected' : ''?>>
                        <?=htmlspecialcharsbx($name)?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <?php endif; ?>
    
    <?php $tabControl->Buttons(); ?>
    <input type="submit" name="Update" value="<?=Loc::getMessage('GARAGE_SAVE_BUTTON')?>" class="adm-btn-save">
    <input type="submit" name="Apply" value="<?=Loc::getMessage('GARAGE_APPLY_BUTTON')?>">
    
</form>

<?php $tabControl->End(); ?>