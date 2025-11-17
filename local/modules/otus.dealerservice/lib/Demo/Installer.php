<?php

namespace Otus\Dealerservice\Demo;

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Iblock\IblockTable;
use CIBlockElement;
use CIBlockSection;
use RuntimeException;
use Otus\Dealerservice\Constants;
use Otus\Dealerservice\Helpers\HighloadHelper;
use Otus\Dealerservice\Helpers\UserFieldsHelper;
use Bitrix\Catalog\Model\Product;
use CGroup;

Loc::loadMessages(__FILE__);

class Installer
{
    private int $iblockId;
    
    /**
     * Installer constructor.
     *
     * Checks if the 'iblock' module is installed.
     * If not, throws a SystemException.
     * Finds the default product catalog ID.
     *
     * @throws SystemException
     */
    public function __construct()
    {
        if (!Loader::includeModule("iblock")) {
            throw new SystemException("Module 'iblock' not installed");
        }
        
        $this->iblockId = $this->findIblockCatalog();
    }
    
    /**
     * Finds the default product catalog ID from the 'crm' module.
     *
     * If the ID is not found in the options, it searches for the
     * catalog with the XML ID 'FUTURE-1C-CATALOG' and type 'CRM_PRODUCT_CATALOG'.
     * If the catalog is not found, it throws a RuntimeException.
     *
     * @return int The default product catalog ID.
     *
     * @throws RuntimeException If the product catalog is not found.
     */
    private function findIblockCatalog(): int
    {
        $iblockId = Option::get("crm", "default_product_catalog_id");
        
        if (empty($iblockId)) {
            $result = IblockTable::getList([
                'filter' => [
                    '=IBLOCK_TYPE_ID' => 'CRM_PRODUCT_CATALOG',
                    '=XML_ID' => 'FUTURE-1C-CATALOG'
                ],
                'select' => ['ID']
            ])->fetch();

            
            $iblockId = $result['ID'] ?? 0;
        }
        
        if (!$iblockId) {
            throw new RuntimeException("Product catalog not found");
        }
        
        return (int)$iblockId;
    }
    
    /**
     * Installs demo data for the dealerservice module.
     *
     * Creates a section, a hlblock, a user group and user fields.
     * Sets the demo data in the options table.
     */
    public function installDemoData(): void
    {
        $sectionId = $this->createSection();
        list($hlblockId, $ufMakeId) = $this->createHLBlockAuto();
        $idUserGroup = $this->createUserGroups();
        $this->createUserFields($hlblockId, $ufMakeId);
        //$this->createDemoParts($sectionId);

        
        Option::set(Constants::MODULE_ID, 'iblock_catalog_section_id', (int)$sectionId);
        Option::set(Constants::MODULE_ID, 'auto_hlblock_id', (int)$hlblockId);
        Option::set(Constants::MODULE_ID, 'user_group_id', (int)$idUserGroup);
        Option::set(Constants::MODULE_ID, 'iblock_catalog_id', (string)$this->iblockId);
    }
    
    
    /**
     * Creates a user group for the dealerservice module demo data.
     *
     * The user group is named 'Garage Users' and has the string ID 'garage_users'.
     * The group is active and has a sort order of 100.
     *
     * @return int The ID of the created user group.
     */
    public function createUserGroups(): int
    {
        $group = new CGroup;
        $arFields = Array(
            "ACTIVE"       => "Y",
            "C_SORT"       => 100,
            "NAME"         => Loc::getMessage("NAME_GROUP_GARAGE"),
            "DESCRIPTION"  => Loc::getMessage("NAME_GROUP_GARAGE"),
            "STRING_ID"      => "garage_users"
        );

        $NEW_GROUP_ID = $group->Add($arFields);

        if (strlen($group->LAST_ERROR)>0) ShowError($group->LAST_ERROR);
        
        return $NEW_GROUP_ID;
    }

    public function uninstallDemoData()
    {
    	$this->deleteUserFields();
    	$this->deleteHLBlocks();
    }
    
    private function deleteUserFields()
    {
    	$fields = [
    		'UF_MAKE',
    		'UF_MODEL',
    		'UF_YEAR',
    		'UF_SUPPORTED_AUTO'
    	];
    	
    	UserFieldsHelper::deleteProperty($fields);
    }
    
    private function deleteHLBlocks()
    {
    	$hlblockId = Option::get(Constants::MODULE_ID, 'auto_hlblock_id');
    	
    	if(!empty($hlblockId))
    	{
    		HighloadHelper::deleteHighloadBlock($hlblockId, Constants::DEALERSERVICE_AUTO_HLBLOCK_TABLE_NAME);
    	}
    }
    
    private function createSection(): int
    {
        $bs = new CIBlockSection;
        $arFields = [
            "ACTIVE" => "Y",
            "IBLOCK_SECTION_ID" => false,
            "IBLOCK_ID" => $this->iblockId,
            "NAME" => Loc::getMessage("TITLE_SECTION_CATALOG"),
            "SORT" => 300,
            "DESCRIPTION" => Loc::getMessage("DESCRIPTION_SECTION_CATALOG"),
            "DESCRIPTION_TYPE" => "text"
        ];
        
        $sectionId = $bs->Add($arFields);
        
        if (!$sectionId) {
            throw new RuntimeException(
                "Ошибка создания раздела: " . ($bs->LAST_ERROR ?: 'Неизвестная ошибка')
            );
        }

        return (int)$sectionId;
    }
    
    private function createHLBlockAuto(): array
    {
        $lang = [
            'ru' => ['NAME' => Loc::getMessage("HLBLOCK_AUTO_LIST_NAME")],
            'en' => ['NAME' => Loc::getMessage("HLBLOCK_AUTO_LIST_NAME")]
        ];
        
        $hlblockId = HighloadHelper::addHLBlock(
            Constants::DEALERSERVICE_AUTO_HLBLOCK_NAME,
            Constants::DEALERSERVICE_AUTO_HLBLOCK_TABLE_NAME,
            $lang
        );
        
        if ($hlblockId <= 0) {
            throw new RuntimeException("Failed to create HLBlock");
        }
        
        $properties = [
            'UF_MAKE' => [
                'ENTITY_ID' => 'HLBLOCK_' . $hlblockId,
                'FIELD_NAME' => 'UF_MAKE',
                'USER_TYPE_ID' => 'string',
                'XML_ID' => '',
                'SORT' => '100',
                'MULTIPLE' => 'N',
                'MANDATORY' => 'Y',
                'SHOW_FILTER' => 'Y',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'Y',
                'SETTINGS' => [],
                'EDIT_FORM_LABEL' => [
                    'en' => Loc::getMessage('HLBLOCK_AUTO_MAKE'),
                    'ru' => Loc::getMessage('HLBLOCK_AUTO_MAKE'),
                ],
                'LIST_COLUMN_LABEL' => [
                    'en' => Loc::getMessage('HLBLOCK_AUTO_MAKE'),
                    'ru' => Loc::getMessage('HLBLOCK_AUTO_MAKE'),
                ],
                'LIST_FILTER_LABEL' => [
                    'en' => Loc::getMessage('HLBLOCK_AUTO_MAKE'),
                    'ru' => Loc::getMessage('HLBLOCK_AUTO_MAKE'),
                ],
                'ERROR_MESSAGE' => [
                    'en' => Loc::getMessage('HLBLOCK_AUTO_MAKE'),
                    'ru' => Loc::getMessage('HLBLOCK_AUTO_MAKE'),
                ],
                'HELP_MESSAGE' => [
                    'en' => '',
                    'ru' => '',
                ],
            ],
            'UF_MODEL' => [
                'ENTITY_ID' => 'HLBLOCK_' . $hlblockId,
                'FIELD_NAME' => 'UF_MODEL',
                'USER_TYPE_ID' => 'string',
                'XML_ID' => '',
                'SORT' => '100',
                'MULTIPLE' => 'N',
                'MANDATORY' => 'Y',
                'SHOW_FILTER' => 'Y',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'Y',
                'SETTINGS' => [],
                'EDIT_FORM_LABEL' => [
                    'en' => Loc::getMessage('HLBLOCK_AUTO_MODEL'),
                    'ru' => Loc::getMessage('HLBLOCK_AUTO_MODEL'),
                ],
                'LIST_COLUMN_LABEL' => [
                    'en' => Loc::getMessage('HLBLOCK_AUTO_MODEL'),
                    'ru' => Loc::getMessage('HLBLOCK_AUTO_MODEL'),
                ],
                'LIST_FILTER_LABEL' => [
                    'en' => Loc::getMessage('HLBLOCK_AUTO_MODEL'),
                    'ru' => Loc::getMessage('HLBLOCK_AUTO_MODEL'),
                ],
                'ERROR_MESSAGE' => [
                    'en' => Loc::getMessage('HLBLOCK_AUTO_MODEL'),
                    'ru' => Loc::getMessage('HLBLOCK_AUTO_MODEL'),
                ],
                'HELP_MESSAGE' => [
                    'en' => '',
                    'ru' => '',
                ],
            ],
            'UF_YEAR' => [
                'ENTITY_ID' => 'HLBLOCK_' . $hlblockId,
                'FIELD_NAME' => 'UF_YEAR',
                'USER_TYPE_ID' => 'integer',
                'XML_ID' => '',
                'SORT' => '100',
                'MULTIPLE' => 'N',
                'MANDATORY' => 'Y',
                'SHOW_FILTER' => 'Y',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'Y',
                'SETTINGS' => [],
                'EDIT_FORM_LABEL' => [
                    'en' => Loc::getMessage('HLBLOCK_AUTO_YAER'),
                    'ru' => Loc::getMessage('HLBLOCK_AUTO_YAER'),
                ],
                'LIST_COLUMN_LABEL' => [
                    'en' => Loc::getMessage('HLBLOCK_AUTO_YAER'),
                    'ru' => Loc::getMessage('HLBLOCK_AUTO_YAER'),
                ],
                'LIST_FILTER_LABEL' => [
                    'en' => Loc::getMessage('HLBLOCK_AUTO_YAER'),
                    'ru' => Loc::getMessage('HLBLOCK_AUTO_YAER'),
                ],
                'ERROR_MESSAGE' => [
                    'en' => Loc::getMessage('HLBLOCK_AUTO_YAER'),
                    'ru' => Loc::getMessage('HLBLOCK_AUTO_YAER'),
                ],
                'HELP_MESSAGE' => [
                    'en' => '',
                    'ru' => '',
                ],
            ],
        ];
        
        $ufMakeId = null;
        
        foreach ($properties as $key => $property) {
            $idProperty = UserFieldsHelper::addProperty($property);
            
            if ($key === 'UF_MAKE' && $idProperty) {
                $ufMakeId = $idProperty;
            }
        }
        
        if (!$ufMakeId) {
            throw new RuntimeException("Failed to create UF_MAKE field");
        }
        
        return [$hlblockId, $ufMakeId];
    }
    
    private function createUserFields(int $hlblockId, int $ufMakeId): void
    {
        $properties = [
            'UF_SUPPORTED_AUTO' => [
                'ENTITY_ID' => 'PRODUCT',
                'FIELD_NAME' => 'UF_SUPPORTED_AUTO',
                'USER_TYPE_ID' => 'hlblock',
                'XML_ID' => '',
                'SORT' => '100',
                'MULTIPLE' => 'Y',
                'MANDATORY' => 'N',
                'SHOW_FILTER' => 'Y',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'Y',
                'SETTINGS' => [
                    'HLBLOCK_ID' => $hlblockId,
                    'HLFIELD_ID' => $ufMakeId, 
                ],
                'EDIT_FORM_LABEL' => [
                    'en' => Loc::getMessage('PRODUCT_SUPPORTED_AUTO'),
                    'ru' => Loc::getMessage('PRODUCT_SUPPORTED_AUTO'),
                ],
                'LIST_COLUMN_LABEL' => [
                    'en' => Loc::getMessage('PRODUCT_SUPPORTED_AUTO'),
                    'ru' => Loc::getMessage('PRODUCT_SUPPORTED_AUTO'),
                ],
                'LIST_FILTER_LABEL' => [
                    'en' => Loc::getMessage('PRODUCT_SUPPORTED_AUTO'),
                    'ru' => Loc::getMessage('PRODUCT_SUPPORTED_AUTO'),
                ],
                'ERROR_MESSAGE' => [
                    'en' => Loc::getMessage('PRODUCT_SUPPORTED_AUTO'),
                    'ru' => Loc::getMessage('PRODUCT_SUPPORTED_AUTO'),
                ],
                'HELP_MESSAGE' => [
                    'en' => '',
                    'ru' => '',
                ],
            ]
        ];
        
        foreach ($properties as $property) {
            UserFieldsHelper::addProperty($property);
        }
    }
    
    private function createDemoParts(int $sectionId): void
    {
        $el = new CIBlockElement;
        $arFields = [
            'IBLOCK_ID' => $this->iblockId,
            'IBLOCK_SECTION_ID' => $sectionId,
            'NAME' => 'Запчасть 1',
            'ACTIVE' => 'Y',
        ]; 
        
        $elementId = $el->Add($arFields);
        
        if (!$elementId) {
            throw new RuntimeException(
                "Ошибка создания демо-запчасти: " . ($el->LAST_ERROR ?: 'Неизвестная ошибка')
            );
        }
    }
}