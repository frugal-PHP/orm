<?php

namespace FrugalPhpPlugin\Orm\Entities;

use FrugalPhpPlugin\Orm\Repositories\AbstractRepository;

abstract class AbstractEntity
{
    use RelationTrait;
    use EntityCacheTrait;

    abstract public static function createFromArray(array $values) : AbstractEntity;

    public function toDatabase(): array
    {
        $output = [];
        foreach (static::getFields() as $property => $column) {
            if (!property_exists($this, $property)) {
                continue;
            }

            $value = $this->$property;

            $output[$column] = match (true) {
                $value instanceof \Ramsey\Uuid\UuidInterface => $value->toString(),
                $value instanceof \BackedEnum => $value->value,
                $value instanceof \DateTimeInterface => $value->format(DATE_ATOM),
                is_object($value) && property_exists($value, 'value') => $value->value,
                default => $value,
            };
        }
        
        return $output;
    }

    public static function getPrimaryKeyNames(): array
    {
        if (!isset(static::$primaryKeyNamesCache[static::class])) {
            self::populateCache();
        }

        return static::$primaryKeyNamesCache[static::class];
    }

    static public function getFields() : array
    {
        if (!isset(static::$fieldsCache[static::class])) {
            self::populateCache();
        }

        return static::$fieldsCache[static::class];
    }

    static public function getTableName() : string
    {
        if (!isset(static::$tableNamesCache[static::class])) {
            self::populateCache();
        }

        return static::$tableNamesCache[static::class];
    }

    static public function getRepository() : AbstractRepository
    {
         if (!isset(static::$repositoriesClassCache[static::class])) {
            self::populateCache();
        }

        return static::$repositoriesClassCache[static::class];
    }
}