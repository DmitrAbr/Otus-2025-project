<?php

namespace Otus\Dealerservice\Helpers;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Highloadblock\HighloadBlockLangTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;

class HighloadHelper
{
	public static function dropTableHLBlock($hlName, $tableName): void
    {
        Loader::includeModule('highloadblock');
        $hlblock = HighloadBlockTable::getList(['filter' => ['NAME' => $hlName]])->fetch();
        if (!$hlblock) {
            $connection = Application::getInstance()->getConnection();
            if ($connection->isTableExists($tableName)) {
                $connection->dropTable($tableName);
            }
        }
    }
    
    public static function deleteHighloadBlock(int $hlBlockId, string $tableName)
    {
    	Loader::includeModule('highloadblock');
    	$application = \Bitrix\Main\Application::getInstance();
		$connection = $application->getConnection();
		
    	try 
    	{
    		$connection->startTransaction();
    		$hlblock = HighloadBlockTable::getList(
    			['filter' => ["=ID" => $hlBlockId]]	
    		)->fetch();
    		
    		if($hlblock["ID"])
    		{
    			HighloadBlockTable::delete($hlblock["ID"]);
    			$connection->commitTransaction();
    		}
    	} 
    	catch (ArgumentException $e) 
    	{
    		$connection->rollbackTransaction();
    		throw $e;
    	}
    }
	
	public static function getEntityDataClass(int $id)
    {
        Loader::includeModule('highloadblock');
        if (empty($id) || $id < 1) {
            throw new SystemException("Can`t get highload block data class. HlBlockId was not set");
        }
        $hlblock = HighloadBlockTable::getById($id)->fetch();
        if ($hlblock) {
            $entity = HighloadBlockTable::compileEntity($hlblock);
            return $entity->getDataClass();
        } else {
            throw new SystemException("Can`t get highload block data class.");
        }
    }
	
	public static function getHlBlockByTableName(string $tableName) :?array
    {
        Loader::includeModule('highloadblock');
        return HighloadBlockTable::getRow(['filter' => ['=TABLE_NAME' => $tableName]]);
    }
	
	public static function addHLBlock(string $name, string $tableName, array $lang = []) : int
	{
		Loader::IncludeModule("highloadblock");
		
		if($hlBlock = self::getHlBlockByTableName($tableName))
		{
			self::dropTableHLBlock($name, $tableName);
			$hlBlock = 0;
		}
		$resDB = HighloadBlockTable::add(["NAME" => $name, "TABLE_NAME" => $tableName]);
		if($resDB->isSuccess())
		{
			$hlBlockId = (int)$resDB->getId();
			if(!empty($lang))
			{
				foreach($lang as $lid => $value)
				{
					HighloadBlockLangTable::add([
						'ID' => $hlBlockId,
						'LID' => $lid,
						'NAME' => $value['NAME']
					]);
				}
			}
			return $hlBlockId;
		}
		else {
			// code...
		}
	}
}