<?php

use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity\Base;
use Otus\Restapi\Orm\DoctorTable;

Loc::loadMessages(__FILE__);

class otus_restapi extends CModule
{
    public $MODULE_ID = 'otus.restapi';
    public $MODULE_SORT = 100;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
	public $PARTNER_URI;
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;

    function __construct()
    {
        $arModuleVersion = array();

        include __DIR__ . "/version.php";

        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_DESCRIPTION = Loc::getMessage("OTUS_RESTAPI_MODULE_DESCRIPTION");
        $this->MODULE_NAME = Loc::getMessage("OTUS_RESTAPI_MODULE_NAME");
        $this->PARTNER_NAME = Loc::getMessage("OTUS_RESTAPI_PARTNER_NAME");
        $this->PARTNER_URI = Loc::getMessage("OTUS_RESTAPI_PARTNER_URI");
    }

    function DoInstall()
    {
        if($this->isVersionD7())
        {
            ModuleManager::registerModule($this->MODULE_ID);
            $this->installDb();
            $this->installEvents();
        }
    }

    function DoUninstall()
    {
        $this->uninstallDb();
        $this->uninstallOptions();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    function installDb()
    {
        Loader::IncludeModule($this->MODULE_ID);
    	
    	$entities = $this->getEntities();
    	
    	foreach($entities as $entity)
    	{
    		if(!Application::getConnection($entity::getConnectionName())->isTableExists($entity::getTableName()))
    		{
    			Base::getInstance($entity)->createDbTable();
    		}
    	}
    }

    function uninstallDb()
    {
        Loader::includeModule($this->MODULE_ID);

        $entities = $this->getEntities();

        foreach($entities as $entity)
        {
            if(Application::getConnection($entity::getConnectionName())->isTableExists($entity::getTableName()))
            {
                $entity::dropTable();
            }
        }
    }

    function uninstallOptions()
    {
        Option::delete($this->MODULE_ID);
    }

    function installEvents()
    {
        $eventManager = EventManager::getInstance();
        $handlers = $this->getHandlers();

        foreach($handlers as $handler)
        {
            $eventManager->registerEventHandler(
                $handler['fromModuleId'],
                $handler['eventType'],
                $this->MODULE_ID,
                $handler['toClass'],
                $handler['toMethod']
            );
        }
    }

    function uninstallEvents()
    {
        $eventManager = EventManager::getInstance();

        $handlers = $this->getHandlers();
        foreach($handlers as $handler)
        {
            $eventManager->unRegisterEventHandler(
                $handler['fromModuleId'],
                $handler['eventType'],
                $this->MODULE_ID,
                $handler['toClass'],
                $handler['toMethod']
            );
        }
    }  

    function getEntities()
    {
        return 
        [
            DoctorTable::class
        ];
    }

    function getHandlers()
    {
        return 
        [
            [
                'fromModuleId' => 'rest',
                'eventType' => 'OnRestServiceBuildDescription',
                'toClass' => '\\Otus\\Restapi\\Events\\Rest',
                'toMethod' => 'OnRestServiceBuildDescriptionHandler',
            ]
        ];
    }

    public function getPath($notDocumentRoot = false)
	{
		if($notDocumentRoot)
		{
			return str_ireplace(Application::getDocumentRoot(), '', dirname(__DIR__));
		}
		else
		{
			return dirname(__DIR__);
		}
	}

    function isVersionD7()
    {
        return version_compare(ModuleManager::getVersion('main'), '20.00.00', '>=');
    }
}