<?php

namespace FrugalPhpPlugin\Orm\Services;

use React\Promise\PromiseInterface;

interface DatabaseInterface
{
    public function execute(string $query, array $parameters = [], ?int $paginationThreshold = null) : PromiseInterface;
    public function getRows($result) : array;
}