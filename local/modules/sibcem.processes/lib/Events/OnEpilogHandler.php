<?php

namespace Sibcem\Processes\Events;

use Bitrix\Main\Context;
use Bitrix\Main\UI\Extension;

class OnEpilogHandler
{
    public static function OnEpilogHandler() 
    {
        $request = Context::getCurrent()->getRequest();

        if($request->isAjaxRequest())
        {
            return;
        }

        $requestPage = $request->getRequestedPage();

        if(preg_match('@/company/personal/user/[0-9]+/@i', $requestPage))
        {
            Extension::load("sibcem.process_menu_item");
        }
    }
}