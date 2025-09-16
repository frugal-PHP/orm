<?php

namespace FrugalPhpPlugin\Orm\Helpers;

use FrugalPhpPlugin\Orm\Entities\AbstractEntity;

final class HydratorHelper
{
    public static function hydrate(
        array $row,
        string $entityClassName
    ) : AbstractEntity
    {
        return $entityClassName::fromArray($row);
    }
}