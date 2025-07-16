<?php

namespace FrugalPhpPlugin\Orm\Helpers;

use Clue\React\SQLite\Result;
use FrugalPhpPlugin\Orm\Entities\AbstractEntities;

final class HydratorHelper
{
    public static function hydrate(
        Result $result,
        AbstractEntities $entity
    ) : AbstractEntities
    {
        $rows = current($result->rows);
        foreach ($entity::getFields() as $classField => $databaseField) {
            if (isset($rows[$databaseField])) {
                $entity->$classField = $rows[$databaseField];
            }
        }

        return $entity;
    }
}