<?php

namespace Otus\Dealerservice\Events;

use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\PhaseSemantics;
use Otus\Dealerservice\Auto;
use Otus\Dealerservice\Orm\AutoTable;
use Bitrix\Main\Diag\Debug;

class CrmUpdateHandler
{
    public static function OnAfterCrmDealUpdateHandler(&$arFields) 
    {
        $autoId = $arFields["UF_CRM_AUTO_ID"];
        $stageSemanticId = $arFields["STAGE_SEMANTIC_ID"];

        if(empty($autoId))
        {
            return;
        }

        if($stageSemanticId == PhaseSemantics::PROCESS)
        {
            Auto::changeAutoStatus($arFields["UF_CRM_AUTO_ID"], AutoTable::IN_WORK, $arFields["ASSIGNED_BY_ID"]);
        }
        elseif($stageSemanticId == PhaseSemantics::SUCCESS)
        {
            Auto::changeAutoStatus($arFields["UF_CRM_AUTO_ID"], AutoTable::DONE, $arFields["ASSIGNED_BY_ID"]);
        }
        else
        {
            Auto::changeAutoStatus($arFields["UF_CRM_AUTO_ID"], AutoTable::REJECTED, $arFields["ASSIGNED_BY_ID"]);
        }

        $autoFields = [
            'MAKE' => $arFields["UF_CRM_MAKE"], 
            'MODEL' => $arFields["UF_CRM_MODEL"], 
            'NUBMER' => $arFields["UF_CRM_NUMBER"], 
            'YEAR' => $arFields["UF_CRM_YEAR"], 
            'COLOR' => $arFields["UF_CRM_COLOR"], 
            'MILEAGE' => $arFields["UF_CRM_MILEAGE"],
            "UPDATED_BY_ID" => $arFields["ASSIGNED_BY_ID"],
        ];

        $result = AutoTable::update($autoId, $autoFields);

        if(!$result->isSuccess())
        {
            Debug::writeToFile($result->getErrorMessages(), 'error-agent', 'local/modules/otus.dealerservice/lib/Events/CrmUpdateHandler.log');
        }
    }
}