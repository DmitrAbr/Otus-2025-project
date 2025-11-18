<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Application;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Config\Option;
use Otus\Dealerservice\Orm\AutoTable;
use Otus\Dealerservice\Demo\Installer;
use Otus\Dealerservice\Userfields\CarSelectorType;

Loc::loadMessages(__FILE__);

class otus_dealerservice extends CModule
{
	public $MODULE_ID = 'otus.dealerservice';
	public $MODULE_SORT = 500;
	public $MODULE_DESCRIPTION;
	public $MODULE_VERSION_DATE;
	public $PARTNER_NAME;
	public $PARTNER_URI;

    function __construct() 
    {
        $arModuleVersion = array();

        // Подключение файла версии, который содержит массив для модуля
        include __DIR__ . "/version.php";
        
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME = Loc::getMessage("OTUS_MODULE_NAME_DEALER");
        $this->MODULE_DESCRIPTION = Loc::getMessage("OTUS_MODULE_DESCRIPTION_DEALER");

        $this->PARTNER_NAME = Loc::getMessage("OTUS_PARTNER_NAME");
        $this->PARTNER_URI = Loc::getMessage("OTUS_PARTNER_URI");

        // Если указано, то на странице редактирования групп будет отображаться этот модуль
        $this->MODULE_GROUP_RIGHTS = "Y";
    }
    
    function DoInstall()
    {
    	ModuleManager::RegisterModule($this->MODULE_ID);
    	$this->installDB();
    	$this->installEvents();
    	$this->installDemo();
    }
    
    function installDB()
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
    
    function installEvents()
    {
    	$eventManager = EventManager::getInstance();
    	
    	$eventManager->registerEventHandler(
    		'crm',
    		'onEntityDetailsTabsInitialized',
			$this->MODULE_ID,
			'\\Otus\\Dealerservice\\Events\\ContactTabs',
			'updateTabs'
    	);
    	
    	$handlers = $this->getHandlers();
    	
        foreach ($handlers as $handler) {
            if (!$handler['compatible']) {
                $eventManager->registerEventHandler(
                    $handler['fromModuleId'],
                    $handler['eventType'],
                    $this->MODULE_ID,
                    $handler['toClass'],
                    $handler['toMethod'],
                    99999
                );
            } else {
                $eventManager->registerEventHandlerCompatible(
                    $handler['fromModuleId'],
                    $handler['eventType'],
                    $this->MODULE_ID,
                    $handler['toClass'],
                    $handler['toMethod'],
                    99999
                );
            }
        }
    }
    
    function installDemo()
    {
    	Loader::IncludeModule($this->MODULE_ID);
    	
    	$installer = new Installer;
    	
    	$installer->installDemoData();
    }
    
    function DoUninstall()
    {
    	$this->uninstallDB();
    	$this->uninstallEvents();
    	$this->uninstallDemoData();
        $this->uninstallOptions();
    	ModuleManager::UnRegisterModule($this->MODULE_ID);
    }
    
    function uninstallOptions()
    {
    	Option::delete($this->MODULE_ID);
    }

    function uninstallDemoData()
    {
    	Loader::IncludeModule($this->MODULE_ID);
    	
    	$installer = new Installer;
    	
    	$installer->uninstallDemoData();
    }
    
    function uninstallDB()
    {
    	Loader::IncludeModule($this->MODULE_ID);
    	
    	$entities = $this->getEntities();
    	
    	foreach($entities as $entity)
    	{
    		$entity::dropTable();
    	}
    }
    
    function uninstallEvents()
    {
    	$eventManager = EventManager::getInstance();
    	
    	$eventManager->unRegisterEventHandler(
    		'crm',
    		'onEntityDetailsTabsInitialized',
			$this->MODULE_ID,
			'\\Otus\\Dealerservice\\Events\\ContactTabs',
			'updateTabs'
    	);
    	
    	$handlers = $this->getHandlers();
        foreach ($handlers as $handler) {
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
    	$entities = [
    		AutoTable::class
    	];
    	
    	return $entities;
    }
    
    function getHandlers()
    {
    	
    }
}