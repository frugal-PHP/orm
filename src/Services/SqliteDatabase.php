<?php

namespace FrugalPhpPlugin\Orm\Services;

use Clue\React\SQLite\DatabaseInterface as SQLiteDatabaseInterface;
use Clue\React\SQLite\Factory;
use Clue\React\SQLite\Result;
use FrugalPhpPlugin\Orm\Interfaces\DatabaseInterface;
use React\EventLoop\Loop;
use React\Promise\PromiseInterface;

use function React\Async\await;

final class SqliteDatabase implements DatabaseInterface
{
    private \Clue\React\SQLite\DatabaseInterface $client;

    public function __construct(string $databasePathName) {
        $factory = new Factory(Loop::get());
        $this->client = await($factory->open($databasePathName));
        $this->client->exec("PRAGMA busy_timeout = 3000")->then(null, fn($e) => null);
        $this->client->exec("PRAGMA foreign_keys = ON")->then(null, fn($e) => null);
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

    public function getRows($result) : array 
    {
        return $result->rows ?? [];
    }

    public function getLastInsertId($result) : int
    {
        return $result->insertId;
    }
}