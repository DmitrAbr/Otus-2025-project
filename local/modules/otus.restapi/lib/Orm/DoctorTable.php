<?php

namespace Otus\Restapi\Orm;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Rest\RestException;
use Bitrix\Main\Diag\Debug;

Loc::loadMessages(__FILE__);
class DoctorTable extends DataManager
{
    public static function getTableName()
    {
        return 'otus_restapi_doctor';
    }

    public static function getMap()
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),

            (new StringField('FULL_NAME'))
            	->configureTitle(Loc::getMessage("NAME_FIELD_FULL_NAME"))
                ->configureRequired(true),
                
            (new StringField('SPECIALITY'))
            	->configureTitle(Loc::getMessage("NAME_FIELD_SPECIALITY"))
                ->configureRequired(true),
                
            (new IntegerField('WORK_EXPERIENCE'))
            	->configureTitle(Loc::getMessage("NAME_FIELD_WORK_EXPERIENCE"))
                ->configureRequired(true),
        ];
    }

    public static function dropTable()
    {
        $connection = \Bitrix\Main\Application::getConnection();
        $connection->dropTable(self::getTableName());
    }

    private static function logToFile($data, $method = '')
    {
        $logFile = $_SERVER['DOCUMENT_ROOT'] . '/local/rest.log';
        
        $logData = [
            'timestamp' => (new DateTime())->format('Y-m-d H:i:s'),
            'method' => $method,
            'data' => $data
        ];
        
        Debug::writeToFile($logData, '', $logFile);
    }

    /**
     * Метод добавления записи
     */
    public static function addRestData($arParams, $navStartPage, \CRestServer $server)
    {
        try {
            self::logToFile(['action' => 'add', 'params' => $arParams], __FUNCTION__);
            
            $result = self::add($arParams);
            
            if ($result->isSuccess()) {
                $id = $result->getId();
                self::logToFile(['action' => 'add_success', 'id' => $id], __FUNCTION__);
                
                return [
                    'success' => true,
                    'data' => [
                        'id' => $id,
                        'message' => 'Запись успешно добавлена'
                    ]
                ];
            } else {
                $errors = $result->getErrorMessages();
                self::logToFile(['action' => 'add_error', 'errors' => $errors], __FUNCTION__);
                
                throw new \Exception(
                    json_encode([
                        'success' => false,
                        'error' => $errors,
                        'error_code' => 'ADD_ERROR'
                    ], JSON_UNESCAPED_UNICODE),
                    400
                );
            }
            
        } catch (\Exception $e) {
            self::logToFile(['action' => 'add_exception', 'exception' => $e->getMessage()], __FUNCTION__);
            
            throw new RestException(
                $e->getMessage(),
                'ADD_ERROR',
                \CRestServer::STATUS_WRONG_REQUEST
            );
        }
    }

    /**
     * Метод получения записей
     */
    public static function getRestData($arParams, $navStartPage, \CRestServer $server)
    {
        try {
            self::logToFile(['action' => 'get', 'params' => $arParams], __FUNCTION__);
            
            $select = $arParams["SELECT"] ?? ['*'];
            $filter = $arParams["FILTER"] ?? [];
            $order = $arParams["ORDER"] ?? ['ID' => 'DESC'];
            $limit = $arParams["LIMIT"] ?? 50;
            $offset = $arParams["OFFSET"] ?? 0;

            $result = self::getList([
                'filter' => $filter,
                'select' => $select,
                'order' => $order,
                'limit' => $limit,
                'offset' => $offset
            ]);

            if ($result) {
                $data = $result->fetchAll();
                self::logToFile(['action' => 'get_success', 'count' => count($data)], __FUNCTION__);
                
                return [
                    'success' => true,
                    'data' => $data,
                ];
            } else {
                self::logToFile(['action' => 'get_empty', 'filter' => $filter], __FUNCTION__);
                
                throw new \Exception(
                    json_encode([
                        'success' => false,
                        'error' => 'Записи не найдены',
                        'error_code' => 'NOT_FOUND'
                    ], JSON_UNESCAPED_UNICODE),
                    404
                );
            }
            
        } catch (\Exception $e) {
            self::logToFile(['action' => 'get_exception', 'exception' => $e->getMessage()], __FUNCTION__);
            
            throw new RestException(
                $e->getMessage(),
                'GET_ERROR',
                \CRestServer::STATUS_NOT_FOUND
            );
        }
    }

    /**
     * Метод обновления записи
     */
    public static function updateRestData($arParams, $navStartPage, \CRestServer $server)
    {
        try {
            self::logToFile(['action' => 'update', 'params' => $arParams], __FUNCTION__);
            
            if (empty($arParams['ID'])) {
                throw new \Exception('ID записи не указан', 400);
            }
            
            $id = (int)$arParams['ID'];
            $updateData = $arParams['FIELDS'] ?? $arParams;
            unset($updateData['ID']);
            
            if (empty($updateData)) {
                throw new \Exception('Данные для обновления не указаны', 400);
            }
            
            $result = self::update($id, $updateData);
            
            if ($result->isSuccess()) {
                self::logToFile(['action' => 'update_success', 'id' => $id], __FUNCTION__);
                
                return [
                    'success' => true,
                    'data' => [
                        'id' => $id,
                        'message' => 'Запись успешно обновлена'
                    ]
                ];
            } else {
                $errors = $result->getErrorMessages();
                self::logToFile(['action' => 'update_error', 'id' => $id, 'errors' => $errors], __FUNCTION__);
                
                throw new \Exception(
                    json_encode([
                        'success' => false,
                        'error' => $errors,
                        'error_code' => 'UPDATE_ERROR'
                    ], JSON_UNESCAPED_UNICODE),
                    400
                );
            }
            
        } catch (\Exception $e) {
            self::logToFile(['action' => 'update_exception', 'exception' => $e->getMessage()], __FUNCTION__);
            
            $status = $e->getCode() ?: \CRestServer::STATUS_WRONG_REQUEST;
            
            throw new RestException(
                $e->getMessage(),
                'UPDATE_ERROR',
                $status
            );
        }
    }

    /**
     * Метод удаления записи
     */
    public static function deleteRestData($arParams, $navStartPage, \CRestServer $server)
    {
        try {
            self::logToFile(['action' => 'delete', 'params' => $arParams], __FUNCTION__);
            
            if (empty($arParams['ID'])) {
                throw new \Exception('ID записи не указан', 400);
            }
            
            $id = (int)$arParams['ID'];
            $result = self::delete($id);
            
            if ($result->isSuccess()) {
                self::logToFile(['action' => 'delete_success', 'id' => $id], __FUNCTION__);
                
                return [
                    'success' => true,
                    'data' => [
                        'id' => $id,
                        'message' => 'Запись успешно удалена'
                    ]
                ];
            } else {
                $errors = $result->getErrorMessages();
                self::logToFile(['action' => 'delete_error', 'id' => $id, 'errors' => $errors], __FUNCTION__);
                
                throw new \Exception(
                    json_encode([
                        'success' => false,
                        'error' => $errors,
                        'error_code' => 'DELETE_ERROR'
                    ], JSON_UNESCAPED_UNICODE),
                    400
                );
            }
            
        } catch (\Exception $e) {
            self::logToFile(['action' => 'delete_exception', 'exception' => $e->getMessage()], __FUNCTION__);
            
            $status = $e->getCode() ?: \CRestServer::STATUS_WRONG_REQUEST;
            
            throw new RestException(
                $e->getMessage(),
                'DELETE_ERROR',
                $status
            );
        }
    }
}