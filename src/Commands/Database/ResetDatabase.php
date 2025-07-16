<?php

namespace FrugalPhpPlugin\Orm\Commands\Database;

use FrugalPhpPlugin\Orm\Services\SqliteDatabase;
use React\EventLoop\Loop;
use function React\Async\await;

class ResetDatabase
{
    public static function run()
    {
        $loop = Loop::get();

        $databaseFilePathName = getenv('ROOT_DIR') . "/database/bdd.sqlite";
        if (file_exists($databaseFilePathName)) {
            unlink($databaseFilePathName);
        }

        $sql = file_get_contents(getenv('ROOT_DIR') . "/database/bdd.sql");
        $queries = array_filter(array_map('trim', explode(';', $sql)));

        $loop->futureTick(function () use ($queries) {
            \React\Async\async(function () use ($queries) {
                await(SqliteDatabase::execute("PRAGMA journal_mode = WAL;"));
                foreach ($queries as $query) {
                    if ($query === '') continue;

                    try {
                        await(SqliteDatabase::execute($query));
                    } catch (\Throwable $e) {
                        echo "Erreur SQL : " . $e->getMessage() . "\n";
                        echo "Requête : " . $query . "\n";
                        exit(1);
                    }
                }

                Loop::stop();
            })();
        });

        $loop->run();

        return 0;
    }
}