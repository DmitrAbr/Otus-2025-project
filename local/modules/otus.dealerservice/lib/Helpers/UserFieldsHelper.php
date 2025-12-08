<?php

namespace Otus\Dealerservice\Helpers;

use CUserTypeEntity;
use CUserFieldEnum;

class UserFieldsHelper
{
    /**
     * Adds a user field to the database.
     *
     * @param array $property an array containing the properties of the user field to be added.
     * @return int the ID of the added user field.
     */
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
    
    
    /**
     * Deletes user fields from the database.
     *
     * @param array $propertiesName an array of field names to be deleted.
     */
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