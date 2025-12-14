<?php

namespace Sibcem\Processes\Demo;

use Sibcem\Processes\Constants;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

class Installer
{
    private $type_iblock;

    public function __construct()
    {
        if(!Loader::includeModule('iblock'))
        {
            throw new \Exception("Module 'iblock' not installed");
        }
    }

    public function install()
    {
        if (!$this->typeIblockExists()) 
        {
            $this->createTypeIblock();
        } else 
        {
            $this->type_iblock = Constants::TYPE_IBLOCK_ID;
        }
        
        Option::set(Constants::MODULE_ID, 'type_iblock_id', Constants::TYPE_IBLOCK_ID);
    }

    public function uninstall()
    {
        $this->deleteTypeIblock();
    }

    private function typeIblockExists(): bool
    {
        $typeIblock = \CIBlockType::GetByID(Constants::TYPE_IBLOCK_ID)->Fetch();
        return !empty($typeIblock);
    }

    private function createTypeIblock(): void
    {
        $arFields = [
            'ID' => Constants::TYPE_IBLOCK_ID,
            'SECTIONS' => 'Y',
            'IN_RSS' => 'N',
            'SORT' => 150,
            'LANG' => [
                'ru' => [
                    'NAME' => Loc::getMessage("NAME_TYPE_IBLOCK"),
                    'SECTION_NAME' => Loc::getMessage("NAME_SECTIONS_IBLOCK"),
                    'ELEMENT_NAME' => Loc::getMessage("NAME_ELEMENT_IBLOCK")
                ],
            ]
        ]; 

        $obBlockType = new \CIBlockType;
        $res = $obBlockType->Add($arFields);
        
        if(!$res) {
            throw new \Exception($obBlockType->LAST_ERROR);
        }
        
        $this->type_iblock = Constants::TYPE_IBLOCK_ID;
    }

    private function deleteTypeIblock(): void
    {
        if ($this->typeIblockExists()) 
            {
            $res = \CIBlock::GetList(
                [],
                [
                    'TYPE' => Constants::TYPE_IBLOCK_ID,
                    'CHECK_PERMISSIONS' => 'N'
                ]
            );
            
            if (!$res->Fetch()) 
            {
                \CIBlockType::Delete(Constants::TYPE_IBLOCK_ID);
            }
        }
    }
}