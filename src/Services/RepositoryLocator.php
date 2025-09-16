<?php

namespace FrugalPhpPlugin\Orm\Services;

use Frugal\Core\Services\FrugalContainer;
use InvalidArgumentException;
use Pahada\Tracks\Repositories\AbstractRepository;
use RuntimeException;

class RepositoryLocator
{
    /** @var array<class-string, AbstractRepository> */
    protected array $repositoryCache = [];

    public function get(string $entityClass) : AbstractRepository {
        if(isset($this->repositoryCache[$entityClass])) {
            return $this->repositoryCache[$entityClass];
        }

        if(!class_exists($entityClass)) {
            throw new InvalidArgumentException("This entity class doesn't exist($entityClass)");
        }

        $reflection = new \ReflectionClass($entityClass);
        $attributes = $reflection->getAttributes(\Doctrine\ORM\Mapping\Entity::class);
        if(empty($attributes)) {
            throw new RuntimeException("Unable to find Entity Annotation on this entity class($entityClass)");
        }

        $entityAttribute = $attributes[0]->newInstance();
        if($entityAttribute->repositoryClass === null) {
            throw new RuntimeException("No repository set in Entity attribute($entityClass)");
        }

        if(!class_exists($entityAttribute->repositoryClass)) {
            throw new RuntimeException("This repository class doesn't exist($entityAttribute->repositoryClass)");
        }

        if (!is_subclass_of($entityAttribute->repositoryClass, AbstractRepository::class)) {
            throw new RuntimeException("This repository class doesn't extend ".AbstractRepository::class."($entityAttribute->repositoryClass)");
        }

        $repositoryClass = new $entityAttribute->repositoryClass(FrugalContainer::getInstance()->get('orm'));
        if($repositoryClass->getManagedEntityClass() !== $entityClass) {
            throw new RuntimeException("This repository doesn't manage this entity($entityAttribute->repositoryClass)");
        }
        $this->repositoryCache[$entityClass] = $repositoryClass;

        return $repositoryClass;
    }
}