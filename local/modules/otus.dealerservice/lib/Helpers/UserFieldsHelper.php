<?php

namespace Otus\Dealerservice\Helpers;

use CUserTypeEntity;
use CUserFieldEnum;

class UserFieldsHelper
{
	public static function addProperty(array $property): int
    {
        $CUserTypeEntity = new CUserTypeEntity();
        $rows = CUserTypeEntity::GetList(
            [],
            [
                'FIELD_NAME' => $property['FIELD_NAME'],
                'ENTITY_ID' => $property['ENTITY_ID']
            ]
        );
        if (!$rows->fetch()) {
            $property['ID'] = $CUserTypeEntity->Add($property);
            if (isset($property['ENUM_VALUES'])) {
                $enums = [];
                $i = 0;
                foreach ($property['ENUM_VALUES'] as $enum) {
                    $enums['n' . $i] = $enum;
                    $i++;
                }
                $CUserFieldEnum = new CUserFieldEnum();
                $CUserFieldEnum->SetEnumValues($property['ID'], $enums);
            }
            return $property['ID'];
        }
        return 0;
    }
    
    public static function deleteProperty(array $propertiesName): void
    {
		foreach($propertiesName as $propertyName)
		{
			$userFields = CUserTypeEntity::GetList([],['FIELD_NAME' => $propertyName]);
			if($userField = $userFields->Fetch())
			{
				$CUserTypeEntity = new CUserTypeEntity;
				$CUserTypeEntity->Delete($userField['ID']);
			}
		}
    }
}