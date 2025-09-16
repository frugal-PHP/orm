<?php

/**
 * Ce DTO va mémoriser les metadatas des entités parsés
 * C'est à lui que l'on fera référence pour les relations, le nom des champs bdd et leurs type etc...
 */

readonly class EntityMetadataDTO
{
    public function __construct(
        private array $relations
    ) {}
}