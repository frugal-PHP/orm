<?php

namespace FrugalPhpPlugin\Orm\Repositories;

use DateTime;
use FrugalPhpPlugin\Orm\Entities\AbstractEntity;
use FrugalPhpPlugin\Orm\Entities\SoftDeleteTrait;
use FrugalPhpPlugin\Orm\Interfaces\DatabaseInterface;
use FrugalPhpPlugin\Orm\Interfaces\RepositoryInterface;
use InvalidArgumentException;
use React\Promise\PromiseInterface;

abstract class AbstractRepository implements RepositoryInterface
{
    protected array $entityFields;
    protected string $entityTableName;
    protected string $managedEntityClassName;

    public function __construct(
        protected DatabaseInterface $db
    ) {
        $this->managedEntityClassName = $this->getManagedEntityClass();
        $this->entityTableName = $this->managedEntityClassName::getTableName();
        $this->entityFields = $this->managedEntityClassName::getFields();
    }

    abstract public function getManagedEntityClass() : string;

    public function getDatabaseManager() : DatabaseInterface
    {
        return $this->db;
    }
    
    /**
     * @return PromiseInterface<array>
     */
    public function findAll() : PromiseInterface
    {
        $table = '`' . $this->entityTableName . '`';
        $query   = "SELECT * FROM $table";

        return $this->execute($query, [])
            ->then(fn($result) => $this->db->getRows($result));
    }

    /**
     * @return PromiseInterface<array>
     */
    public function findBy(array $properties) : PromiseInterface
    {
        if(empty($properties)) {
            throw new InvalidArgumentException(message: "Properties array must contains at least one property");
        }

        // On va check que les clefs sont valides pour notre entité.
        if(array_diff(array_keys($properties), array_keys($this->entityFields))) {
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

        return $this->execute($query, $parameters)
            ->then(fn($result) => $this->db->getRows($result));
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
        $parameters['id'] = $entityId;

        // Managing soft delete
        if (in_array(SoftDeleteTrait::class, class_uses($this->managedEntityClassName))) {
            $query = $this->buildUpdateQuery();
            $parameters = $this->buildParameters(['deletedAt' => new DateTime()]);

            return $this->db->execute(query: $query, parameters: $parameters);
        }

        return $this->db->execute(
            query: "DELETE FROM ".$this->entityTableName." WHERE id = :id",
            parameters: $parameters
        );
    }

    private function buildInsertQuery() : string
    {
        $columns = implode(', ', array_values($this->entityFields));
        $placeholders = implode(', ', array_map(fn($key) => ':' . $key, array_keys($this->entityFields)));

        return "INSERT OR REPLACE INTO `".$this->entityTableName."`($columns) VALUES ($placeholders)";
    }

    private function buildUpdateQuery() : string
    {
        $fields = array_diff($this->entityFields, ['id']);
        $placeholders = implode(', ', array_map(function($key) use($fields) { return $fields[$key].'=:' . $key; }, array_keys($fields)));

        return "UPDATE ".$this->entityTableName." SET $placeholders WHERE id=:id";
    }

    private function buildParameters(array $values): array
    {
        $params = [];
        foreach (array_keys($this->entityFields) as $classFieldName) {
            if (!array_key_exists($classFieldName, $values)) {
                continue;
            }

            $val = $values[$classFieldName] === null ? null : (string) $values[$classFieldName];

            if(is_array($val)) {
                foreach(array_values($val) as $index => $value) {
                    $params[":$$classFieldName"."_$index"] = $value;
                }
            } else {
                $params[":$classFieldName"] = $val;
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