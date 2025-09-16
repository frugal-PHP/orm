<?php

namespace FrugalPhpPlugin\Orm\Entities;

use Frugal\Core\Services\FrugalContainer;
use FrugalPhpPlugin\Orm\Repositories\AbstractRepository;
use RuntimeException;

trait EntityCacheTrait
{
    protected static array $fieldsCache = [];
    protected static array $tableNamesCache = [];
    protected static array $primaryKeyNamesCache = [];
    protected static AbstractRepository $repositoriesClassCache;

    static private function populateCache() : void
    {
        $reflection = new \ReflectionClass(static::class);

        static::populateFieldsCache($reflection);
        static::populateTableNameCache($reflection);
        static::populatePrimaryKeyNames($reflection);
        static::populateRepositoryClassCache($reflection);
    }

    static private function populateRepositoryClassCache($reflection) : void
    {
        $attributes = $reflection->getAttributes(\Doctrine\ORM\Mapping\Entity::class);
        if(!$attributes) {
            throw new \RuntimeException("Repository is not defined via #[Entity(repositoryClass: '...')] in class ".static::class);
        }

        $repository = $attributes[0]->newInstance();
        $repositoryClassName = $repository->repositoryClass;
        static::$repositoriesClassCache[static::class] = new $repositoryClassName(FrugalContainer::getInstance()->get('orm'));
    }

    static private function populateFieldsCache($reflection) : void
    {
        $fields = [];

        foreach ($reflection->getProperties() as $property) {
            $name = $property->getName();

            // Utiliser un attribut #[Column(name: "xxx")] si précisé
            $attributes = $property->getAttributes(\Doctrine\ORM\Mapping\Column::class);
            if ($attributes) {
                /** @var \Doctrine\ORM\Mapping\Column $column */
                $column = $attributes[0]->newInstance();
                $columnName = $column->name ?? $name;
            } else {
                // Sinon, appliquer la naming strategy (camelCase -> snake_case)
                $columnName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $name));
            }

            $fields[$name] = $columnName;
        }

        static::$fieldsCache[static::class] = $fields;
    }

    static private function populateTableNameCache($reflection) : void
    {
        $attributes = $reflection->getAttributes(\Doctrine\ORM\Mapping\Table::class);

        if (!$attributes) {
            throw new \RuntimeException("Table name not defined via #[Table(name: '...')] in class ".static::class);
        }

        /** @var \Doctrine\ORM\Mapping\Table $table */
        $table = $attributes[0]->newInstance();
        static::$tableNamesCache[static::class] = $table->name;
    }

    /**
     * Renvoi un tableau 
     */
    static private function populatePrimaryKeyNames($reflection) : void
    {
        $primaryKeyNames = [];
        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(\Doctrine\ORM\Mapping\Id::class);

            if (!empty($attributes)) {
                $primaryKeyNames[] = $property->getName();
            }
        }

        if(empty($primaryKeyNames)) {
            throw new \RuntimeException("Primary keys are not defined via #[Id] in class ".static::class);
        }

        static::$primaryKeyNamesCache[static::class] = $primaryKeyNames;
    }
}