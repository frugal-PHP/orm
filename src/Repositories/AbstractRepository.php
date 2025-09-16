<?php

namespace FrugalPhpPlugin\Orm\Repositories;

use Exception;
use FrugalPhpPlugin\Orm\Exceptions\EntityNotFoundException;
use FrugalPhpPlugin\Orm\Services\DatabaseInterface;
use FrugalPhpPlugin\Orm\Entities\AbstractEntity;
use InvalidArgumentException;
use React\Promise\PromiseInterface;

abstract class AbstractRepository
{
    protected array $entityFields;
    protected string $entityTableName;
    protected array $entityPrimaryKeyName;

    public function __construct(
        protected DatabaseInterface $db
    ) {
        $entityClassName = $this->getManagedEntityClass();
        $this->entityTableName = $entityClassName::getTableName();
        $this->entityFields = $entityClassName::getFields();
        $this->entityPrimaryKeyName = $entityClassName::getPrimaryKeyNames();
    }

    abstract public function getManagedEntityClass() : string;

    public function findOneById($id) : PromiseInterface
    {   
        return $this->findBy(['uuid' => $id])
            ->then(function(array $rows) {
                if(empty($rows)) {
                    throw new EntityNotFoundException(message: "Entity not found or you don't have the right to get it.");
                }
                if(count($rows) > 1) {
                    throw new Exception("Not unique entity but findOneById called");
                }
                return $this->getManagedEntityClass()::fromArray(current($rows));
            });
    }

    public function findOneBy(array $properties) : PromiseInterface
    {
        return $this->findBy($properties)
            ->then(function(array $rows) {
                if(empty($rows)) {
                    throw new EntityNotFoundException(message: "Entity not found or you don't have the right to get it.");
                }
                if(count($rows) > 1) {
                    throw new Exception("Not unique entity but findOneBy called");
                }
                return $this->getManagedEntityClass()::fromArray(current($rows));
            });
    }

    public function findBy(array $properties) : PromiseInterface
    {
        if(empty($properties)) {
            throw new InvalidArgumentException(message: "Properties array must contains at least one property");
        }

        // On va check que les clefs sont valides pour notre entité.
        if(array_diff(array_keys($this->entityFields), array_keys($properties))) {
            throw new InvalidArgumentException(message: "Invalid entity fields in properties argument");
        }
        
        $whereConditions = ["1=1"];
        foreach($properties as $entityFieldName => $value) {
            $col = $this->entityFields[$entityFieldName];

            if ($value === null) {
                $whereConditions[] = "$col IS NULL";
                continue;
            }

            if(is_array($value)) {
                if($value === []) {
                    continue;
                }
                $placeholders = [];
                foreach (array_values($value) as $i => $_) {
                    $placeholders[] = ":{$entityFieldName}_$i";
                }
                $condition = "IN (" . implode(",", $placeholders) . ")";
            } else {
                $condition = "= :$entityFieldName";
            }
            $whereConditions[] = "$col $condition";
        }

        $whereClause = implode(" AND ", $whereConditions);
        $parameters = $this->buildParameters($properties);

        $table = '`' . $this->entityTableName . '`';
        $query   = "SELECT * FROM $table WHERE $whereClause";

        return $this->execute($query, $parameters);
    }

    public function doesOneExist(array $fields) : PromiseInterface
    {
       return $this->checkUnicityConstraint($fields)->then(fn(bool $result) => !$result);
    }

    public function checkUnicityConstraint(array $fields) : PromiseInterface
    {
         if(empty($fields)) {
            throw new InvalidArgumentException(message: "fields array must contains at least one property");
        }

        $unknownFields = array_diff(array_keys($fields), array_keys($this->entityFields));
        if(count($unknownFields) > 0) {
            throw new InvalidArgumentException(message: "Unknown fields : ".implode(",",$unknownFields));
        }

        $whereClause = implode(" AND ", array_map(fn($propertyKey) => $this->entityFields[$propertyKey].'=:'.$this->entityFields[$propertyKey], array_keys($fields)));

        $query = "SELECT 1 FROM ".$this->entityTableName." WHERE ".$whereClause." LIMIT 1";
        $parameters = $this->buildParameters($fields);

        return $this->db->execute(
            query: $query,
            parameters: $parameters
        )
        ->then(fn($rows) => $rows)
        ->then(fn(array $rows)  => empty($rows));
    }

    public function create(AbstractEntity $entity) : PromiseInterface
    {
        $query = $this->buildInsertQuery();
        $parameters = $this->buildParameters($entity->toDatabase());

        return $this->db->execute(query: $query, parameters: $parameters);
    }

    public function update(AbstractEntity $entity): PromiseInterface
    {
        $query = $this->buildUpdateQuery();
        $parameters = $this->buildParameters($entity->toDatabase());

        return $this->db->execute(query: $query, parameters: $parameters);
    }

    public function delete(string|int $entityId): PromiseInterface
    {        
        $parameters[$this->entityPrimaryKeyName] = $entityId;

        return $this->db->execute(
            query: "DELETE FROM ".$this->entityTableName." WHERE $this->entityPrimaryKeyName = :".$this->entityPrimaryKeyName,
            parameters: $parameters
        );
    }

    private function buildInsertQuery() : string
    {
        $columns = implode(', ', array_values($this->entityFields));
        $placeholders = implode(', ', array_map(fn($key) => ':' . $this->entityFields[$key], array_keys($this->entityFields)));

        return "INSERT OR REPLACE INTO `".$this->entityTableName."`($columns) VALUES ($placeholders)";
    }

    private function buildUpdateQuery() : string
    {
        $fields = array_diff($this->entityFields, $this->entityPrimaryKeyNames);
        $placeholders = implode(', ', array_map(fn($bddFieldName) => $bddFieldName.'=:' . $bddFieldName, array_values($fields)));

        $whereClause = implode(" AND ", array_map(fn($primaryKeyFieldName) => $primaryKeyFieldName.'=:'.$primaryKeyFieldName, $this->entityPrimaryKeyNames));
        return "UPDATE ".$this->entityTableName." SET $placeholders WHERE $whereClause";
    }

    private function buildParameters(array $values): array
    {
        $params = [];
        foreach ($this->entityFields as $classFieldName => $bddFieldName) {
            if (!isset($values[$classFieldName])) {
                continue;
            }

            $val = $values[$classFieldName];
            if(is_array($val)) {
                foreach(array_values($val) as $index => $value) {
                    $params[":$bddFieldName"."_$index"] = $value;
                }
            } else {
                $params[":$bddFieldName"] = $val;
            }
        }

        return $params;
    }

    /**
     * @return PromiseInterface<array>
     */
    protected function execute(
        string $query, 
        array $parameters
    ) : PromiseInterface
    {
        return $this->db->execute(
            $query, 
            $parameters, 
            null
        );
    }

    /**
     * @return PromiseInterface<PaginatedResult>
     */
    protected function executeWithPagination(
        string $query, 
        array $parameters,
        int $paginationThreshold
    ) : PromiseInterface
    {
        return $this->db->execute(
            $query, 
            $parameters, 
            $paginationThreshold
        );
    }
}