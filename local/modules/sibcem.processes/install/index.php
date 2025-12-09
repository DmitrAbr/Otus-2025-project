<?php

use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\InvalidPathException;

Loc::loadMessages(__FILE__);

class sibcem_processes extends CModule
{
    public $MODULE_ID = 'sibcem.processes';
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
        $this->MODULE_DESCRIPTION = Loc::getMessage("SIBCEM_PROCESSES_MODULE_DESCRIPTION");
        $this->MODULE_NAME = Loc::getMessage("SIBCEM_PROCESSES_MODULE_NAME");
        $this->PARTNER_NAME = Loc::getMessage("SIBCEM_PROCESSES_PARTNER_NAME");
        $this->PARTNER_URI = Loc::getMessage("SIBCEM_PROCESSES_PARTNER_URI");
    }

    function DoInstall()
    {
        if($this->isVersionD7())
        {
            ModuleManager::registerModule($this->MODULE_ID);
            $this->installFiles();
            $this->installEvents();
        }
    }

    function DoUninstall()
    {
        $this->uninstallEvents();
        $this->uninstallFiles();
        ModuleManager::unRegisterModule($this->MODULE_ID);
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

    function getHandlers()
    {
        return [
            [
                'fromModuleId' => 'main',
                'eventType' => 'OnEpilog',
                'toClass' => '\\Sibcem\\Processes\\Events\\OnEpilogHandler',
                'toMethod' => 'OnEpilogHandler',
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