<?php

namespace Otus\Restapi\Orm;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Event;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\Localization\Loc;
use Bitrix\Rest\RestException;

Loc::loadMessages(__FILE__);
class DoctorTable extends DataManager
{
    public static function getTableName()
    {
        return 'otus_restapi_doctor';
    }

    public static function getMap()
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),

            (new StringField('FULL_NAME'))
            	->configureTitle(Loc::getMessage("NAME_FIELD_FULL_NAME"))
                ->configureRequired(true),
                
            (new StringField('SPECIALITY'))
            	->configureTitle(Loc::getMessage("NAME_FIELD_SPECIALITY"))
                ->configureRequired(true),
                
            (new IntegerField('WORK_EXPERIENCE'))
            	->configureTitle(Loc::getMessage("NAME_FIELD_WORK_EXPERIENCE"))
                ->configureRequired(true),
        ];
    }

    public static function dropTable()
    {
        $connection = \Bitrix\Main\Application::getConnection();
        $connection->dropTable(self::getTableName());
    }

    public static function addRestData($arParams, $navStartm, \CRestServer $server)
    {
        $result = self::add($arParams);

        if($result->isSuccess())
        {
            $id = $result->getId();
            $arParams["ID"] = $id;
            return $id;
        }
        else{
            throw new RestException(
                json_encode($result->getErrorMessages(), JSON_UNESCAPED_UNICODE),
                RestException::ERROR_ARGUMENT,
                \CRestServer::STATUS_OK
            );
        }
    }
}