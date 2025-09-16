<?php

namespace FrugalPhpPlugin\Orm;

readonly class PaginatedResult
{
    public function __construct(
        private string $entityClassName,
        public int $totalNumRecords,
        public int $currentNumRecords,
        public ?string $nextId,
        public array $data
    )
    {}

    /**
     * @return array<AbstractEntity> 
     */
    public function getEntities() : array
    {
        return array_map(fn($row) => $this->entityClassName::fromArray($row), $this->data);
    }

    public function hasMore() : bool
    {
        return $this->nextId !== null;
    }

    public function getEntityClassName(): string
    {
        return $this->entityClassName;
    }

    public function isEmpty(): bool
    {
        return $this->currentNumRecords === 0;
    }
}