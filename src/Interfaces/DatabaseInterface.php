<?php

namespace FrugalPhpPlugin\Orm\Interfaces;

use React\Promise\PromiseInterface;

interface DatabaseInterface
{
    public function execute(string $query, array $parameters = [], ?int $paginationThreshold = null) : PromiseInterface;
    public function getRows($result) : array;
    public function getLastInsertId($result) : int;
}