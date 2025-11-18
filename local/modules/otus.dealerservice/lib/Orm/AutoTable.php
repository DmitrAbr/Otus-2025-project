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
            	->configureTitle(Loc::getMessage("NAME_FIELD_COLOR")),
            
            (new IntegerField('MILEAGE'))
            	->configureTitle(Loc::getMessage("NAME_FIELD_MILEAGE")),

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

    public static function demoDataUpload()
    {
        $autos = [
            [
                'CLIENT_ID' => 1,
                'CREATED_BY_ID' => 1,
                'UPDATED_BY_ID' => 1,
                'STATUS' => self::NEW,
                'MAKE' => 'BMW',
                'MODEL' => 'X5',
                'MILEAGE' => 10000,
                'NUMBER' => 'A123BC',
                'YEAR' => 2019,
                'COLOR' => 'Желтый',
            ],
            [
                'CLIENT_ID' => 5,
                'CREATED_BY_ID' => 3,
                'UPDATED_BY_ID' => 7,
                'STATUS' => self::REJECTED,
                'MAKE' => 'Toyota',
                'MILEAGE' => 558923,
                'MODEL' => 'Camry',
                'NUMBER' => 'E456KX',
                'YEAR' => 2024,
                'COLOR' => 'Черный',
            ],
            [
                'CLIENT_ID' => 5,
                'CREATED_BY_ID' => 5,
                'UPDATED_BY_ID' => 7,
                'STATUS' => self::IN_WORK,
                'MAKE' => 'Ford',
                'MILEAGE' => 75463,
                'MODEL' => 'Focus',
                'NUMBER' => 'K123OM',
                'YEAR' => 1999,
                'COLOR' => 'Синий',
            ],
            [
                'CLIENT_ID' => 3,
                'CREATED_BY_ID' => 5,
                'UPDATED_BY_ID' => 7,
                'STATUS' => self::DONE,
                'MAKE' => 'Chervolet',
                'MILEAGE' => 8456,
                'MODEL' => 'Camaro',
                'NUMBER' => 'T521TT',
                'YEAR' => 2019,
                'COLOR' => 'Красный',
            ],
        ];

        foreach($autos as $auto)
        {
            $result = self::add($auto);
            if(!$result->isSuccess())
            {   
                throw new \RuntimeException(print_r($result->getErrorMessages(), true));
            }
        }
    }
}