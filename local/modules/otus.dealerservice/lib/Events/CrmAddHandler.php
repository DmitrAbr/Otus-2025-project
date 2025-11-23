<?php

namespace Otus\Dealerservice\Events;

use Bitrix\Main\Diag\Debug;
use Otus\Dealerservice\Orm\AutoTable;

class CrmAddHandler
{
    public static function OnBeforeCrmDealAddHandler(&$arFields)
    {
        $auto = AutoTable::getList([
           'filter' => [
                'NUMBER' => $arFields['UF_CRM_NUMBER']
           ],
        ]);

        if(empty(!$auto))
        {
                
        }
    }
}