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
                'otus.restapi.get' => [__CLASS__, 'get'],
                'otus.restapi.delete' => [__CLASS__, 'delete'],
                'otus.restapi.update' => [__CLASS__, 'update'],
            ]
        ];
    }

    public static function add($arParams, $navStartm, \CRestServer $server)
    {
        return DoctorTable::addRestData($arParams, $navStartm, $server);
    }

    public static function get($arParams, $navStartm, \CRestServer $server)
    {
        return DoctorTable::getRestData($arParams, $navStartm, $server);
    }

    public static function delete($arParams, $navStartm, \CRestServer $server)
    {
        return DoctorTable::deleteRestData($arParams, $navStartm, $server);
    }

    public static function update($arParams, $navStartm, \CRestServer $server)
    {
        return DoctorTable::updateRestData($arParams, $navStartm, $server);
    }
}