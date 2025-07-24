<?php

namespace FrugalPhpPlugin\Orm\Entities;

use JsonSerializable;

abstract class AbstractEntity
{
    protected bool $isNew = false;

    abstract static public function getFields() : array;
    abstract static public function getTableName() : string;
    abstract public static function getPrimaryKeyName(): string;
    abstract public function toDatabase(): array;
}