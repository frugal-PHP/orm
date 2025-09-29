<?php

namespace FrugalPhpPlugin\Orm\Helpers;

use Frugal\Core\Services\FrugalContainer;
use FrugalPhpPlugin\Orm\Interfaces\RepositoryInterface;

class RepositoryHelper
{
    public static function getRepository(string $entityClassName) : ?RepositoryInterface
    {
        return FrugalContainer::getInstance()
            ->get('repositoryLocator')
            ->get($entityClassName);
    }
}