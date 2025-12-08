<?php

namespace Otus\Dealerservice;

use Bitrix\Main\Config\Option;
use Otus\Dealerservice\Constants;
use Bitrix\Main\Loader;
use Bitrix\Catalog\ProductTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Diag\Debug;

class Parts
{
    public const DEFAULT_COUNT = 10;

    /**
     * Returns an array of parts from the catalog.
     *
     * This function retrieves a list of parts from the catalog and returns them as an array.
     *
     * @return array
     */

    public static function getListParts(): array
    {
        Loader::includeModule('catalog');

        $parts = ProductTable::getList([
            'filter' => ['TYPE' => ProductTable::TYPE_OFFER]
        ]);

        return $parts->fetchAll();
    }

    /**
     * Retrieves the count of parts from the warehouse API.
     *
     * @return int The count of parts from the warehouse API.
     *
     * @throws \RuntimeException If there is an error making the request to the warehouse API.
     */
    public static function getCountPartFromWarehouse(): int
    {
        $CurlOptions = [
            CURLOPT_URL            => Constants::WAREHOUSE_API_COUNT_PART,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FAILONERROR => true,
        ];

        $ch = curl_init();

        curl_setopt_array($ch, $CurlOptions);

        try {
            $data = curl_exec($ch);
            $info = curl_getinfo($ch);
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to make request to the warehouse API: ' . $e->getMessage(), 0, $e);
        }

        if (curl_errno($ch) || substr($info['http_code'], 0, 1) !== '2') {
            throw new \RuntimeException('Failed to get count part from warehouse API: ' . curl_error($ch), 0);
        }

        return (int)$data;
    }

    
    /**
     * Starts the business process of buying parts.
     *
     * This function creates a new element in the Bizproc module and starts the business process of buying parts.
     *
     * @param array $data The data to pass to the business process.
     *
     * @return void
     */
    public static function startProcessBuy(array $data)
    {
        Loader::includeModule('bizproc');

        $el = new \CIBlockElement;
        $arFields = [
          'ACTIVE' => 'Y',
          'IBLOCK_ID' => Option::get(Constants::MODULE_ID, 'iblock_purchase_requests_id'),
          'NAME' => Loc::getMessage("NAME_ELEMENT_PARTS", ["#DATE#" => date("d.m.Y")]),
          'CREATED_BY' => 1,
          'PROPERTY_VALUES' => $data
        ];

        $ID = $el->Add($arFields);

        if($ID <= 0)
        {
            Debug::writeToFile($el->LAST_ERROR, 'error-agent', 'local/modules/otus.dealerservice/lib/Agents/UpdateCountParts.log');
            return;
        } 

        self::startBizprocBuy($ID);
    }

    
    /**
     * Starts the business process of buying parts.
     *
     * This function starts the business process of buying parts by triggering the Bizproc module.
     *
     * @param int $ID The ID of the Bizproc element to start the process for.
     *
     * @return void
     */
    public static function startBizprocBuy(int $ID)
    {
        Loader::includeModule('bizproc');

        \CBPDocument::AutoStartWorkflows(
            array('lists', 'BizprocDocument', "iblock_".Option::get(Constants::MODULE_ID, 'iblock_purchase_requests_id')),
            \CBPDocumentEventType::Create, 
            array('lists', 'BizprocDocument', $ID), 
            array(), 
            $errors
        );
        if(!empty($errors))
        {
            Debug::writeToFile($errors, 'error-agent', 'local/modules/otus.dealerservice/lib/Agents/UpdateCountParts.log');
        }
    }
}