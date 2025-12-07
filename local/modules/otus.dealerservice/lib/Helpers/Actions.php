<?php

namespace Otus\Dealerservice\Helpers;

use Bitrix\Main\Config\Option;

class Actions
{
    public static function checkRightsUser($userId)
    {
        $groups = \CUser::GetUserGroup($userId);

        return in_array(Option::get('otus.dealerservice', 'user_group_id'), $groups);
    }
}