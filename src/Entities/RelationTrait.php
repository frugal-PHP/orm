<?php

namespace FrugalPhpPlugin\Orm\Entities;

use Frugal\Core\Services\FrugalContainer;
use React\Promise\PromiseInterface;

trait RelationTrait
{
    protected function relationOneToMany(
        string $targetEntityNamespace, 
        string $targetProperty, 
        string $id
    ) : PromiseInterface
    {
        $repository = $targetEntityNamespace::getRepository();
        
        return $repository->findBy([$targetProperty => $id])
            ->then(fn($rows) => empty($rows) 
                ? [] 
                : array_map(fn($row) => $targetEntityNamespace::createFromArray($row), $rows)
        );
    }

    protected function relationManyToMany(
        string $pivotTableName,
        string $pivotOriginEntityFieldName,
        string $pivotTargetEntityFieldName,
        string $targetEntityNamespace,
        string $targetEntityIdFieldName,
        string $id
    ) : PromiseInterface
    {
        /** @var SqliteDatabase $orm */
        $orm = FrugalContainer::getInstance()->get('orm');
        $query = "SELECT $pivotTargetEntityFieldName AS target_id FROM $pivotTableName WHERE $pivotOriginEntityFieldName = :id";
        $parameters = ['id' => $id];

        return $orm->execute(query: $query, parameters: $parameters)
            ->then(function($rows) use ($targetEntityNamespace, $targetEntityIdFieldName, $pivotTargetEntityFieldName) {
                if(empty($rows)) {
                    return [];
                }

                $repository = $targetEntityNamespace::getRepository();
                $ids = array_column($rows, 'target_id');

                return $repository->findBy([$targetEntityIdFieldName => $ids])
                    ->then(fn($rows)=> empty($rows) 
                        ? [] 
                        : array_map(fn($row) => $targetEntityNamespace::createFromArray($row), $rows));
            });
    }

    protected function relationManyToOne(
        string $targetEntityNamespace,
        string $id
    ) : PromiseInterface
    {
        $repository = $targetEntityNamespace::getRepository();
        
        return $repository->findById($id)
            ->then(fn($row) => empty($row) 
                ? null
                : $targetEntityNamespace::createFromArray($row)
        );
    }

    protected function relationOneToOne(
        string $targetEntityNamespace,
        string $id
    ) : PromiseInterface {
        return $this->relationManyToOne(
            $targetEntityNamespace,
            $id
        );
    }
}