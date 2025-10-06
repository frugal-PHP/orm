<?php

namespace FrugalPhpPlugin\Orm\Entities;

use FrugalPhpPlugin\Orm\Interfaces\EntityInterface;
use FrugalPhpPlugin\Orm\Repositories\AbstractRepository;
use Ramsey\Uuid\UuidInterface;

abstract class AbstractEntity implements EntityInterface
{
    use RelationTrait;
    use EntityCacheTrait;

    abstract public static function createFromArray(array $values) : AbstractEntity;

    public function toDatabase(): array
    {
        $output = [];
        foreach (array_keys(static::getFields()) as $property) {
            if (!property_exists($this, $property)) {
                continue;
            }

            $value = $this->$property;

            $output[$property] = match (true) {
                $value instanceof UuidInterface => $value->toString(),
                $value instanceof \BackedEnum => $value->value,
                $value instanceof \DateTimeInterface => $value->format(DATE_ATOM),
                is_object($value) && property_exists($value, 'value') => $value->value,
                default => is_null($value) ? null : (string) $value,
            };
        }

        return $output;
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