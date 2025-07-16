<?php

namespace FrugalPhpPlugin\Orm\Services;

use React\EventLoop\Loop;
use React\Promise\PromiseInterface;

final class SqliteDatabase implements DatabaseInterface
{
    private \Clue\React\SQLite\DatabaseInterface $client;

    public static function create(string $databasePathName, int $timeout = 3000) : PromiseInterface
    {
        $self = new self();
        $factory = new \Clue\React\SQLite\Factory(Loop::get());

        return $factory->open($databasePathName)
            ->then(function($client) use ($timeout, $self) {
                $client->exec("PRAGMA busy_timeout = $timeout");
                $this->client = $client;
                return $self;
            });
    }

    public function execute(string $query, array $parameters = []) : PromiseInterface
    {
        $action = strtoupper(strtok(ltrim($query), " \t\n\r"));
        if(strtoupper($action) === "SELECT") {
            return $this->client->query($query, $parameters);
        }
        
        return $this->client->query($query, $parameters);
    }
}