<?php

namespace FrugalPhpPlugin\Orm\Interfaces;

use FrugalPhpPlugin\Orm\Entities\AbstractEntity;
use React\Promise\PromiseInterface;

interface RepositoryInterface
{
    public function findAll() : PromiseInterface;
    public function findBy(array $properties) : PromiseInterface;
    public function create(AbstractEntity $entity) : PromiseInterface;
    public function update(AbstractEntity $entity): PromiseInterface;
    public function delete(string|int $entityId): PromiseInterface;
    public function getDatabaseManager() : DatabaseInterface;
}