<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Otus\Dealerservice\Orm\AutoTable;
use Otus\Dealerservice\Demo\Installer;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\InvalidPathException;
use Bitrix\Main\SystemException;
use Otus\Dealerservice\Events\AutoUpdateHandler;

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

        include __DIR__ . "/version.php";
        
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME = Loc::getMessage("OTUS_MODULE_NAME_DEALER");
        $this->MODULE_DESCRIPTION = Loc::getMessage("OTUS_MODULE_DESCRIPTION_DEALER");

        $this->PARTNER_NAME = Loc::getMessage("OTUS_PARTNER_NAME");
        $this->PARTNER_URI = Loc::getMessage("OTUS_PARTNER_URI");

        $this->MODULE_GROUP_RIGHTS = "Y";
    }
    
    function DoInstall()
    {
        if($this->isVersionD7())
		{
            ModuleManager::RegisterModule($this->MODULE_ID);
            $this->installDB();
            $this->installEvents();
            $this->installDemo();
            $this->installFiles();
			$this->installAgents();
        }
        else {
			throw new SystemException(Loc::getMessage("OTUS_DEALERSERVICE_INSTALL_ERROR_VERSION"));
		}
    }
    
	function installAgents()
	{
		\CAgent::AddAgent(
		"Otus\Dealerservice\Agents\UpdateCountParts::updateCountParts();", 
		$this->MODULE_ID, 
		"N", 
		86400);
	}

    function installFiles()
    {
        $component_path = $this->getPath(). '/install/components';
        $to_component_path = $_SERVER["DOCUMENT_ROOT"].'/bitrix/components';
		$js_path = $this->getPath(). '/install/js';
        $to_js_path = $_SERVER["DOCUMENT_ROOT"].'/bitrix/js';

        if(Directory::isDirectoryExists($js_path))
        {
            CopyDirFiles($js_path, $to_js_path, true, true);
        }
        else
        {
            throw new InvalidPathException($js_path);
        }

		if(Directory::isDirectoryExists($component_path))
		{
			CopyDirFiles($component_path, $to_component_path, true, true);
		}
		else
		{
			throw new InvalidPathException($component_path);
		}
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
        $this->uninstallFiles();
		$this->uninstallAgents();
    	ModuleManager::UnRegisterModule($this->MODULE_ID);
    }
    
	function uninstallAgents()
	{
		\CAgent::RemoveAgent(
		"Otus\Dealerservice\Agents\UpdateCountParts::updateCountParts();",
		$this->MODULE_ID
		);
	}

    function uninstallFiles()
    {
        $component_path = $this->getPath(). '/install/components';
		$js_path = $this->getPath(). '/install/js';

        if(Directory::isDirectoryExists($js_path))
		{
			$installed_components = new \DirectoryIterator($js_path);
			foreach($installed_components as $component)
			{
				if($component->isDir() && !$component->isDot())
				{
					$target_path = $_SERVER["DOCUMENT_ROOT"].'/bitrix/js/'.$component->getFilename();
					if(Directory::isDirectoryExists($target_path))
					{
						Directory::deleteDirectory($target_path);
					}
				}
			}
		}
		else
		{
			throw new InvalidPathException($component_path);
		}

		if(Directory::isDirectoryExists($component_path))
		{
			$installed_components = new \DirectoryIterator($component_path);
			foreach($installed_components as $component)
			{
				if($component->isDir() && !$component->isDot())
				{
					$target_path = $_SERVER["DOCUMENT_ROOT"].'/bitrix/components/'.$component->getFilename();
					if(Directory::isDirectoryExists($target_path))
					{
						Directory::deleteDirectory($target_path);
					}
				}
			}
		}
		else
		{
			throw new InvalidPathException($component_path);
		}
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
		Loader::IncludeModule($this->MODULE_ID);
    	return [
			[
				'fromModuleId' => 'crm',
				'eventType' => 'onEntityDetailsTabsInitialized',
				'toClass' => '\\Otus\\Dealerservice\\Events\\ContactTabs',
				'toMethod' => 'updateTabs',
			],
			[
				'fromModuleId' => 'crm',
				'eventType' => 'OnBeforeCrmDealAdd',
				'toClass' => '\\Otus\\Dealerservice\\Events\\CrmAddHandler',
				'toMethod' => 'OnBeforeCrmDealAddHandler',
			],
			[
				'fromModuleId' => 'crm',
				'eventType' => 'OnAfterCrmDealUpdate',
				'toClass' => '\\Otus\\Dealerservice\\Events\\CrmUpdateHandler',
				'toMethod' => 'OnAfterCrmDealUpdateHandler',
			],
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

    public function isVersionD7()
	{
		return version_compare(ModuleManager::getVersion('main'), '20.00.00', '>=');
	}
}