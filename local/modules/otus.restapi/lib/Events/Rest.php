<?php

namespace Otus\Restapi\Events;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Otus\Restapi\Orm\DoctorTable;

Loc::loadMessages(__FILE__);
class Rest 
{
    public static function OnRestServiceBuildDescriptionHandler()
    {
        Loc::getMessage("REST_SCOPE_OTUS.RESTAPI");

        return [
            'otus.restapi' => [
                'otus.restapi.add' => [__CLASS__, 'add'],
            ],
        ];
    }

    public static function add($arParams, $navStartm, \CRestServer $server)
    {
        return DoctorTable::addRestData($arParams, $navStartm, $server);
    }
}