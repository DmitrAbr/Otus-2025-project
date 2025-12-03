<?php

namespace Otus\Dealerservice\Agents;

use Otus\Dealerservice\Parts;
use Bitrix\Main\Loader;
use Bitrix\Catalog\ProductTable;
use Bitrix\Main\Diag\Debug;

class UpdateCountParts
{
    public static function updateCountParts(): string
    {
        try {
            Loader::includeModule('catalog');
            
            $listParts = Parts::getListParts();
            $nullParts = [];
            
            foreach ($listParts as $part) {
                $newQuantity = Parts::getCountPartFromWarehouse();
                
                if ($newQuantity === 0) {
                    $nullParts[] = $part['ID'];
                }
                
                $result = ProductTable::update($part['ID'], [
                    'QUANTITY' => $newQuantity
                ]);
                
                if (!$result->isSuccess()) {
                    Debug::writeToFile($result->getErrorMessages(), 'error-agent', 'local/modules/otus.dealerservice/lib/Agents/UpdateCountParts.log');
                }
            }
            
            if (!empty($nullParts)) {
                $data = [
                    'IDS_PARTS' => $nullParts,
                    'KOLICHESTVO_ZAKUPAEMYKH_ZAPCHASTEY' => Parts::DEFAULT_COUNT
                ];
                Parts::startProcessBuy($data);
            }
            
        } catch (\Exception $e) {
            Debug::writeToFile($e->getMessage(), 'error-agent', 'local/modules/otus.dealerservice/lib/Agents/UpdateCountParts.log');
        }
        
        return __CLASS__.'::updateCountParts();';
    }
}