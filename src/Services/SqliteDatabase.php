<?php

namespace FrugalPhpPlugin\Orm\Services;

use Clue\React\SQLite\Result;
use React\EventLoop\Loop;
use React\Promise\PromiseInterface;

final class SqliteDatabase implements DatabaseInterface
{
    private \Clue\React\SQLite\DatabaseInterface $client;

    public function __construct(string $databasePathName) {
        $factory = new \Clue\React\SQLite\Factory(Loop::get());
        $this->client = $factory->openLazy($databasePathName);
        $this->client->exec("PRAGMA busy_timeout = 3000");
    }

    public function execute(string $query, array $parameters = []) : PromiseInterface
    {        
        return $this->client->query($query, $parameters)
        ->then(function(Result $result) {
            return $result->rows;
        });
    }

    public function close(): void
    {
        $this->client->quit();
    }
}