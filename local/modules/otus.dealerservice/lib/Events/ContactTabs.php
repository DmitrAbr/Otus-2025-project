<?php

namespace Otus\Dealerservice\Events;

use Otus\Dealerservice\Orm\AutoTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\EventResult;
use Bitrix\Main\Event;

Loc::loadMessages(__FILE__);

class ContactTabs
{
	static function updateTabs(Event $event): EventResult
	{
		$entityTypeId = $event->getParameter('entityTypeID');
		
		if($entityTypeId === \CCrmOwnerType::Contact)
		{
			$tabs = $event->getParameter('tabs');

			$tabs[]=[
				'id' => 'tab_garage',
				'name' => Loc::getMessage("OTUS_DEALERSERVICE_TAB_TITLE"),
				'enabled' => true,
				'loader' => [
					'serviceUrl' => sprintf(
						'/bitrix/components/otus.dealerservice/auto.list/lazyload.ajax.php?site=%s&%s',
						\SITE_ID,
						\bitrix_sessid_get(),
					),	
					'componentData' => [
						'template' => '',
						'params' => [
							'contactID' => $event->getParameter('entityID')			
						]
					]
				],
			];
			
			return new EventResult(EventResult::SUCCESS, ['tabs' => $tabs]);	
		}
		return new EventResult(EventResult::SUCCESS);
	}
}