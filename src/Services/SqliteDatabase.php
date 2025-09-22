<?php

namespace FrugalPhpPlugin\Orm\Services;

use Clue\React\SQLite\DatabaseInterface as SQLiteDatabaseInterface;
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
        $this->client->exec("PRAGMA foreign_keys = ON");

    }

    public function getDB() : SQLiteDatabaseInterface
    {
        return $this->client;
    }

    /**
     * @return PromiseInterface<Result>
     */
    public function execute(
        string $query, 
        array $parameters = [], 
        ?int $paginationThreshold = null
    ) : PromiseInterface
    {        
        return $this->client->query($query, $parameters);
    }

    public function close(): void
    {
        $this->client->quit();
    }

    /**
     * @var Result $result
     */
    public function getRows($result) : array 
    {
        return $result->rows ?? [];
    }
}