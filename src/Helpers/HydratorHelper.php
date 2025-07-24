<?php

namespace FrugalPhpPlugin\Orm\Helpers;

use FrugalPhpPlugin\Orm\Entities\AbstractEntity;

final class HydratorHelper
{
    public static function hydrate(
        array $row,
        AbstractEntity $entity
    ) : AbstractEntity
    {
        foreach ($entity::getFields() as $classField => $databaseField) {
            if (isset($row[$databaseField])) {
                if( method_exists($entity, 'set'.ucfirst($classField))) {
                    $entity->{'set'.ucfirst($classField)}($row[$databaseField]);
                } else {
                    $entity->$classField = $row[$databaseField];                    
                }
            }
        }

        return $entity;
    }
}