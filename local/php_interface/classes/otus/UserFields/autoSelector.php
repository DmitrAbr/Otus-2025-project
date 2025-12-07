<?php

namespace Test;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserField\Types\BaseType;
use CUserTypeManager;
use Bitrix\Highloadblock as HL;

Loc::loadMessages(__FILE__);

class BrandModelYearType extends BaseType
{
    public const USER_TYPE_ID = 'sibcem_brand_model_year';
    
    /**
     * Return user type description
     */
    public static function GetUserTypeDescription(): array
    {
        return [
            "USER_TYPE_ID" => self::USER_TYPE_ID,
            "CLASS_NAME" => __CLASS__,
            "DESCRIPTION" => "aaaaaaaaaaa",
            "BASE_TYPE" => CUserTypeManager::BASE_TYPE_INT
        ];
    }

    public static function GetEditFormHTML($arUserField, $arHtmlControl): string
    {
        Loader::includeModule('highloadblock');
        
        // ID вашего Highload-блока с автомобилями
        $hlblockId = 5; // Замените на реальный ID
        
        // Получаем данные из Highload-блока
        $data = self::getHlData($hlblockId);
        
        $currentValue = $arHtmlControl['VALUE'];
        $fieldName = $arHtmlControl['NAME'];
        
        // Разбираем текущее значение (если есть)
        $selectedBrand = '';
        $selectedModel = '';
        $selectedYear = '';
        
        if ($currentValue) {
            // Предполагаем, что значение хранится как JSON или ID записи
            // В данном случае будем использовать ID записи Highload-блока
            $selectedRecord = self::getHlRecordById($hlblockId, $currentValue);
            if ($selectedRecord) {
                $selectedBrand = $selectedRecord['UF_MAKE'];
                $selectedModel = $selectedRecord['UF_MODEL']; 
                $selectedYear = $selectedRecord['UF_YEAR'];
            }
        }
        
        // Формируем HTML с тремя вложенными select'ами
        $html = '<div class="brand-model-year-selector">';
        
        // Select для марок
        $html .= '<select name="'.$fieldName.'_brand" id="'.$fieldName.'_brand" class="brand-select" onchange="updateModelSelect(this.value, \''.$fieldName.'\')">';
        $html .= '<option value="">'.Loc::getMessage("SIBCEM_SELECT_BRAND").'</option>';
        foreach (array_keys($data) as $brand) {
            $selected = ($brand == $selectedBrand) ? 'selected' : '';
            $html .= '<option value="'.htmlspecialcharsbx($brand).'" '.$selected.'>'.$brand.'</option>';
        }
        $html .= '</select>';
        
        // Select для моделей
        $html .= '<select name="'.$fieldName.'_model" id="'.$fieldName.'_model" class="model-select" onchange="updateYearSelect(this.value, \''.$fieldName.'\')" '.($selectedBrand ? '' : 'disabled').'>';
        $html .= '<option value="">'.Loc::getMessage("SIBCEM_SELECT_MODEL").'</option>';
        if ($selectedBrand && isset($data[$selectedBrand])) {
            foreach (array_keys($data[$selectedBrand]) as $model) {
                $selected = ($model == $selectedModel) ? 'selected' : '';
                $html .= '<option value="'.htmlspecialcharsbx($model).'" '.$selected.'>'.$model.'</option>';
            }
        }
        $html .= '</select>';
        
        // Select для годов
        $html .= '<select name="'.$fieldName.'_year" id="'.$fieldName.'_year" class="year-select" onchange="updateHiddenField(\''.$fieldName.'\')" '.($selectedModel ? '' : 'disabled').'>';
        $html .= '<option value="">'.Loc::getMessage("SIBCEM_SELECT_YEAR").'</option>';
        if ($selectedBrand && $selectedModel && isset($data[$selectedBrand][$selectedModel])) {
            foreach ($data[$selectedBrand][$selectedModel] as $yearRecord) {
                $selected = ($yearRecord['YEAR'] == $selectedYear) ? 'selected' : '';
                $html .= '<option value="'.$yearRecord['ID'].'" '.$selected.'>'.$yearRecord['YEAR'].'</option>';
            }
        }
        $html .= '</select>';
        
        // Скрытое поле с итоговым значением (ID записи)
        $html .= '<input type="hidden" name="'.$fieldName.'" id="'.$fieldName.'" value="'.$currentValue.'">';
        
        $html .= '</div>';
        
        // JavaScript для динамического обновления списков
        $html .= self::getJavaScript($fieldName, $data);
        
        return $html;
    }

    /**
     * Get data from Highload-block
     */
    private static function getHlData($hlblockId): array
    {
        $result = [];
        
        try {
            $hlblock = HL\HighloadBlockTable::getById($hlblockId)->fetch();
            if (!$hlblock) {
                return $result;
            }
            
            $entity = HL\HighloadBlockTable::compileEntity($hlblock);
            $entityDataClass = $entity->getDataClass();
            
            // Получаем все записи
            $records = $entityDataClass::getList([
                'select' => ['ID', 'UF_MAKE', 'UF_MODEL', 'UF_YEAR'],
                'order' => ['UF_MAKE' => 'ASC', 'UF_MODEL' => 'ASC', 'UF_YEAR' => 'ASC']
            ]);
            
            while ($record = $records->fetch()) {
                $brand = $record['UF_MAKE'];
                $model = $record['UF_MODEL'];
                $year = $record['UF_YEAR'];
                
                if (!isset($result[$brand])) {
                    $result[$brand] = [];
                }
                if (!isset($result[$brand][$model])) {
                    $result[$brand][$model] = [];
                }
                
                $result[$brand][$model][] = [
                    'ID' => $record['ID'],
                    'YEAR' => $year
                ];
            }
            
        } catch (\Exception $e) {
            // Обработка ошибок
        }
        
        return $result;
    }

    /**
     * Get single record by ID
     */
    private static function getHlRecordById($hlblockId, $recordId)
    {
        try {
            $hlblock = HL\HighloadBlockTable::getById($hlblockId)->fetch();
            if (!$hlblock) {
                return null;
            }
            
            $entity = HL\HighloadBlockTable::compileEntity($hlblock);
            $entityDataClass = $entity->getDataClass();
            
            return $entityDataClass::getById($recordId)->fetch();
            
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Generate JavaScript for dynamic selects
     */
    private static function getJavaScript($fieldName, $data): string
    {
        $jsonData = json_encode($data);
        
        return "
        <script>
            // Данные для вложенных списков
            var brandModelYearData = {$jsonData};
            
            function updateModelSelect(brand, fieldName) {
                var modelSelect = document.getElementById(fieldName + '_model');
                var yearSelect = document.getElementById(fieldName + '_year');
                var hiddenField = document.getElementById(fieldName);
                
                // Очищаем модели и годы
                modelSelect.innerHTML = '<option value=\"\">".Loc::getMessage("SIBCEM_SELECT_MODEL")."</option>';
                yearSelect.innerHTML = '<option value=\"\">".Loc::getMessage("SIBCEM_SELECT_YEAR")."</option>';
                hiddenField.value = '';
                
                if (!brand) {
                    modelSelect.disabled = true;
                    yearSelect.disabled = true;
                    return;
                }
                
                // Заполняем модели для выбранной марки
                var models = brandModelYearData[brand];
                if (models) {
                    modelSelect.disabled = false;
                    for (var model in models) {
                        var option = document.createElement('option');
                        option.value = model;
                        option.textContent = model;
                        modelSelect.appendChild(option);
                    }
                } else {
                    modelSelect.disabled = true;
                    yearSelect.disabled = true;
                }
            }
            
            function updateYearSelect(model, fieldName) {
                var brandSelect = document.getElementById(fieldName + '_brand');
                var yearSelect = document.getElementById(fieldName + '_year');
                var hiddenField = document.getElementById(fieldName);
                
                // Очищаем годы
                yearSelect.innerHTML = '<option value=\"\">".Loc::getMessage("SIBCEM_SELECT_YEAR")."</option>';
                hiddenField.value = '';
                
                if (!model) {
                    yearSelect.disabled = true;
                    return;
                }
                
                var brand = brandSelect.value;
                var years = brandModelYearData[brand] && brandModelYearData[brand][model];
                if (years) {
                    yearSelect.disabled = false;
                    years.forEach(function(yearData) {
                        var option = document.createElement('option');
                        option.value = yearData.ID;
                        option.textContent = yearData.YEAR;
                        yearSelect.appendChild(option);
                    });
                } else {
                    yearSelect.disabled = true;
                }
            }
            
            function updateHiddenField(fieldName) {
                var yearSelect = document.getElementById(fieldName + '_year');
                var hiddenField = document.getElementById(fieldName);
                
                hiddenField.value = yearSelect.value;
            }
            
            // Инициализация при загрузке
            document.addEventListener('DOMContentLoaded', function() {
                var brandSelect = document.getElementById('{$fieldName}_brand');
                if (brandSelect.value) {
                    updateModelSelect(brandSelect.value, '{$fieldName}');
                    
                    var modelSelect = document.getElementById('{$fieldName}_model');
                    if (modelSelect.value) {
                        updateYearSelect(modelSelect.value, '{$fieldName}');
                    }
                }
            });
        </script>
        ";
    }

    /**
     * Represent value in admin list view
     */
    public static function GetAdminListViewHTML($arUserField, $arHtmlControl): string
    {
        if (intval($arHtmlControl['VALUE'])) {
            // Получаем запись из Highload-блока по ID
            $hlblockId = 1; // Замените на ваш ID
            $record = self::getHlRecordById($hlblockId, intval($arHtmlControl['VALUE']));
            
            if ($record) {
                return $record['UF_MAKE'] . ' - ' . $record['UF_MODEL'] . ' (' . $record['UF_YEAR'] . ')';
            }
        }
        
        return '&nbsp;';
    }

    /**
     * Database column type
     */
    public static function getDbColumnType(): string
    {
        return 'int';
    }
}