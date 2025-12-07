<?php

namespace Otus\Dealerservice;

use Otus\Dealerservice\Orm\AutoTable;
use Bitrix\Crm\DealTable;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Data\UpdateResult;
use Bitrix\Main\Type\DateTime;

class Auto
{
    
    public static function getDeals(int $autoId): array
    {
        Loader::IncludeModule('crm');

        $deals = DealTable::getList([
            'select' => [
                '*', 
                'ASSIGNED_BY_NAME' => 'ASSIGNED_BY.NAME', 
                'ASSIGNED_BY_LAST_NAME' => 'ASSIGNED_BY.LAST_NAME', 
                'ASSIGNED_BY_SECOND_NAME' => 'ASSIGNED_BY.SECOND_NAME',
                'PRODUCT_NAME' => 'PRODUCT_ROW.PRODUCT_NAME',
                'PRODUCT_QUANTITY' => 'PRODUCT_ROW.QUANTITY'
            ],
            'filter' => ['UF_CRM_AUTO_ID' => $autoId]
        ]);

        return $deals->fetchAll();
    }

    public static function changeAutoStatus(int $autoId, string $status, int $userId): UpdateResult
    {
        return AutoTable::update($autoId, [
            'STATUS' => $status,
            'UPDATED_BY_ID' => $userId,
            'UPDATED_AT' => new DateTime()
        ]);
    }

    public static function findAutoByNumber(string $number): ?array
    {
        return AutoTable::getList([
            'filter' => ['NUMBER' => $number],
            'limit' => 1
        ])->fetch() ?: null;
    }

    public static function isAutoWorking(int $autoId): bool
    {
        $auto = AutoTable::getById($autoId)->fetch();
        return $auto['STATUS'] === AutoTable::IN_WORK;
    }
    
    public static function create(array $fields): \Bitrix\Main\ORM\Data\AddResult
    {
        return AutoTable::add($fields);
    }
}