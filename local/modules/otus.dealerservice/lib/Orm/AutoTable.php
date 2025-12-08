<?php

namespace Otus\Dealerservice\Orm;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;
use Bitrix\Crm\ContactTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Crm\DealTable;
use Bitrix\Main\ORM\EntityError;
use Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

class AutoTable extends DataManager
{
    public const NEW = 'NEW';
    public const REJECTED = 'REJECTED';
    public const IN_WORK = 'IN_WORK';
    public const DONE = 'DONE';

    public static function getTableName()
    {
        return 'otus_dealerservice_auto';
    }

    public static function getMap()
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),

            (new IntegerField('CLIENT_ID'))
            	->configureTitle(Loc::getMessage("NAME_FIELD_CLIENT"))
                ->configureRequired(true),
                
            (new IntegerField('CREATED_BY_ID'))
            	->configureTitle(Loc::getMessage("NAME_FIELD_CREATED"))
                ->configureRequired(true),
                
            (new IntegerField('UPDATED_BY_ID'))
            	->configureTitle(Loc::getMessage("NAME_FIELD_UPDATED"))
                ->configureRequired(true),

            (new StringField('STATUS'))
            	->configureTitle(Loc::getMessage("NAME_FIELD_STATUS"))
                ->configureSize(50)
                ->configureDefaultValue('NEW'), //NEW, REJECTED, IN_WORK, DONE
                
            (new StringField('MAKE'))
            	->configureTitle(Loc::getMessage("NAME_FIELD_MAKE")),
            
            (new StringField('MODEL'))
            	->configureTitle(Loc::getMessage("NAME_FIELD_MODEL")),
            
            (new StringField('NUMBER'))
            	->configureTitle(Loc::getMessage("NAME_FIELD_NUMBER")),
            
            (new IntegerField('YEAR'))
            	->configureTitle(Loc::getMessage("NAME_FIELD_YEAR")),
            
            (new StringField('COLOR'))
            	->configureTitle(Loc::getMessage("NAME_FIELD_COLOR"))
                ->configureNullable(true),
            
            (new IntegerField('MILEAGE'))
            	->configureTitle(Loc::getMessage("NAME_FIELD_MILEAGE"))
                ->configureNullable(true),

            (new DatetimeField('CREATED_AT'))
                ->configureDefaultValue(function() {
                    return new DateTime();
                }),

            (new DatetimeField('UPDATED_AT'))
                ->configureDefaultValue(function() {
                    return new DateTime();
                }),

            // Связи
            new Reference(
                'CONTACT',
                ContactTable::class,
                Join::on('this.CLIENT_ID', 'ref.ID')
            ),

            new Reference(
                'CREATED_BY',
                UserTable::class,
                Join::on('this.CREATED_BY_ID', 'ref.ID')
            ),
            
            new Reference(
                'UPDATED_BY',
                UserTable::class,
                Join::on('this.UPDATED_BY_ID', 'ref.ID')
            ),
        ];
    }
    
    public static function dropTable()
    {
        $connection = \Bitrix\Main\Application::getConnection();
        $connection->dropTable(self::getTableName());
    }

    /**
     * Обработчик события после обновления записи в таблице AutoTable.
     * Меняет сделки, соответствующие записи в таблице DealTable.
     * @param Event $event
     * @return EventResult
     */
    public static function onAfterUpdate(Event $event)
    {
        Loader::IncludeModule('crm');
        $result = new EventResult;
        $data = $event->getParameter("fields");
        $id = $event->getParameter("id");
        
        $deals = DealTable::getList([
            'filter' => ['UF_CRM_AUTO_ID' => $id],
        ])->fetchAll();
            
            if(!empty($deals))
            {
                foreach($deals as $deal)
                {
                    $deal["UF_CRM_MAKE"] = $data["MAKE"];
                    $deal["UF_CRM_MODEL"] = $data["MODEL"];
                    $deal["UF_CRM_NUMBER"] = $data["NUMBER"];
                    $deal["UF_CRM_YEAR"] = $data["YEAR"];
                    $deal["UF_CRM_COLOR"] = $data["COLOR"];
                    $deal["UF_CRM_MILEAGE"] = $data["MILEAGE"];
                    $res = DealTable::update($deal["ID"], $deal);
                    if(!$res->isSuccess())
                    {
                        $result->addError(new EntityError($res->getErrorMessages()));
                    }
                }
            }

        return $result;
    }
}