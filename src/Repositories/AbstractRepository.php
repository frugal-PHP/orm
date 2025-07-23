<?php

namespace FrugalPhpPlugin\Orm\Repositories;

use FrugalPhpPlugin\Orm\Services\DatabaseInterface;
use FrugalPhpPlugin\Orm\Entities\AbstractEntity;
use FrugalPhpPlugin\Orm\Helpers\HydratorHelper;
use React\Promise\PromiseInterface;

abstract class AbstractRepository
{
    protected array $entityFields;
    protected string $entityTableName;
    protected string $entityPrimaryKeyName;

    public function __construct(
        protected DatabaseInterface $db
    ) {
        $entityClassName = $this->getManagedEntityClass();
        $this->entityTableName = $entityClassName::getTableName();
        $this->entityFields = $entityClassName::getFields();
        $this->entityPrimaryKeyName = $entityClassName::getPrimaryKeyName();
    }

    abstract public function getManagedEntityClass() : string;

    public function findOneById($primaryKeyValue) : PromiseInterface
    {
        $query = "SELECT * FROM ".$this->entityTableName." WHERE ".$this->entityPrimaryKeyName."= :id";
        $parameters = [':id' => $primaryKeyValue];

        return $this->db->execute($query, $parameters)
            ->then(function(array $row) {
                $className = $this->getManagedEntityClass();
                return empty($row) ? null : HydratorHelper::hydrate(row: $row, entity: new $className);
            });
    }

    private function buildInsertQuery() : string
    {
        $columns = implode(', ', array_values($this->entityFields));
        $placeholders = implode(', ', array_map(fn($key) => ':' . $this->entityFields[$key], array_keys($this->entityFields)));

        return "INSERT INTO ".$this->entityTableName."($columns) VALUES ($placeholders)";
    }

    private function buildUpdateQuery() : string
    {
        $fields = $this->entityFields;
        unset($fields[$this->entityPrimaryKeyName]);
        $placeholders = implode(', ', array_map(fn($key) => $key.'=:' . $key, array_keys($fields)));

        return "UPDATE ".$this->entityTableName." SET $placeholders WHERE $this->entityPrimaryKeyName=:$this->entityPrimaryKeyName";
    }

    private function buildParameters(AbstractEntity $entity): array
    {
        $params = [];
        foreach ($this->entityFields as $classFieldName => $bddFieldName) {
            $params[":$bddFieldName"] = $entity->$classFieldName;
        }

        return $params;
    }

    public function create(AbstractEntity $entity) : PromiseInterface
    {
        $query = $this->buildInsertQuery();
        $parameters = $this->buildParameters($entity);

        return $this->db->execute(query: $query, parameters: $parameters);
    }

    public function update(AbstractEntity $entity): PromiseInterface
    {
        $query = $this->buildUpdateQuery();
        $parameters = $this->buildParameters($entity);

        return $this->db->execute(query: $query, parameters: $parameters);
    }

    public function delete(AbstractEntity $entity): PromiseInterface
    {
        return $this->db->execute(
            query: "DELETE FROM ".$this->entityTableName." WHERE $this->entityPrimaryKeyName = :id",
            parameters: [':id' => $entity->{$this->entityPrimaryKeyName}]
        );
    }
}