<?php

namespace Otus\Dealerservice\Events;

use Bitrix\Main\Localization\Loc;
use Otus\Dealerservice\Auto;
use Otus\Dealerservice\Orm\AutoTable;

class CrmAddHandler
{
    public static function OnBeforeCrmDealAddHandler(&$arFields)
    {
        if (empty($arFields['UF_CRM_NUMBER'])) {
            return true;
        }

        $number = trim($arFields['UF_CRM_NUMBER']);
        $auto = Auto::findAutoByNumber($number);

        if (!empty($auto)) {
            if (Auto::isAutoWorking($auto['ID'])) {
                $arFields['RESULT_MESSAGE'] = Loc::getMessage('AUTO_IN_WORK');
                return false;
            }

            $result = Auto::changeAutoStatus($auto['ID'], AutoTable::IN_WORK, $arFields["ASSIGNED_BY_ID"]);
            
            if (!$result->isSuccess()) {
                $errors = $result->getErrorMessages();
                $arFields['RESULT_MESSAGE'] = implode(', ', $errors);
                return false;
            }

            $arFields["UF_CRM_AUTO_ID"] = $auto['ID'];
        } else {
            $result = AutoTable::add([
                "CLIENT_ID" => $arFields["CONTACT_ID"],
                "MAKE" => $arFields["UF_CRM_MAKE"],
                "MODEL" => $arFields["UF_CRM_MODEL"],
                "NUMBER" => $arFields["UF_CRM_NUMBER"],
                "YEAR" => $arFields["UF_CRM_YEAR"],
                "COLOR" => $arFields["UF_CRM_COLOR"],
                "MILEAGE" => $arFields["UF_CRM_MILEAGE"],
                "CREATED_BY_ID" => $arFields["ASSIGNED_BY_ID"],
                "UPDATED_BY_ID" => $arFields["ASSIGNED_BY_ID"],
                "STATUS" => AutoTable::IN_WORK
            ]);

            if (!$result->isSuccess()) {
                $arFields['RESULT_MESSAGE'] = Loc::getMessage("ERROR_CREATE_DEAL");
                return false;
            }

            $arFields["UF_CRM_AUTO_ID"] = $result->getId();
        }

        return true;
    }
}