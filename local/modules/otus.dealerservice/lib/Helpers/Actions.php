<?php

namespace Otus\Dealerservice\Helpers;

use Bitrix\Main\Config\Option;

class Actions
{
    /**
     * Checks if user has rights to use module.
     * @param int $userId User ID
     * @return bool True if user has rights, false otherwise
     */
    public static function checkRightsUser($userId)
    {
        $groups = \CUser::GetUserGroup($userId);

        return in_array(Option::get('otus.dealerservice', 'user_group_id'), $groups);
    }
}