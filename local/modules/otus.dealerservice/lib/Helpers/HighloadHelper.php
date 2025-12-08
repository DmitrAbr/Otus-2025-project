<?php

namespace Otus\Dealerservice\Helpers;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Highloadblock\HighloadBlockLangTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;

class HighloadHelper
{
	
	/**
	 * Drops the table of the Highload-block with the given name.
	 *
	 * @param string $hlName The name of the Highload-block.
	 * @param string $tableName The table name of the Highload-block.
	 */
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
    
	/**
	 * Deletes the Highload-block with the given ID.
	 *
	 * @param int $hlBlockId The ID of the Highload-block.
	 * @param string $tableName The table name of the Highload-block.
	 */
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
	
	
	/**
	 * Returns the data class of the Highload-block with the given ID.
	 *
	 * If the Highload-block with the given ID does not exist, an exception is thrown.
	 *
	 * @param int $id The ID of the Highload-block.
	 *
	 * @return string The data class of the Highload-block.
	 *
	 * @throws SystemException If the Highload-block with the given ID does not exist.
	 */
	public static function getEntityDataClass(int $id)
    {
        Loader::includeModule('highloadblock');
        if (empty($id) || $id < 1) {
            throw new \SystemException("Can`t get highload block data class. HlBlockId was not set");
        }
        $hlblock = HighloadBlockTable::getById($id)->fetch();
        if ($hlblock) {
            $entity = HighloadBlockTable::compileEntity($hlblock);
            return $entity->getDataClass();
        } else {
            throw new \SystemException("Can`t get highload block data class.");
        }
    }
	

    /**
     * Returns the Highload-block with the given table name.
     *
     * @param string $tableName The table name of the Highload-block.
     *
     * @return array|null The Highload-block with the given table name, or null if it does not exist.
     */
	public static function getHlBlockByTableName(string $tableName) :?array
    {
        Loader::includeModule('highloadblock');
        return HighloadBlockTable::getRow(['filter' => ['=TABLE_NAME' => $tableName]]);
    }
	
	
	/**
	 * Adds a new Highload-block with the given name and table name.
	 *
	 * @param string $name The name of the Highload-block.
	 * @param string $tableName The table name of the Highload-block.
	 * @param array $lang The language data for the Highload-block.
	 *
	 * @return int The ID of the added Highload-block.
	 */
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
		}else{
			return 0;
		}
	}
}