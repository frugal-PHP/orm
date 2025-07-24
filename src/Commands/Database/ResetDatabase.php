<?php

namespace FrugalPhpPlugin\Orm\Commands\Database;

use Frugal\Core\Services\FrugalContainer;
use function React\Async\await;

class ResetDatabase
{
    public static function run()
    {
        $databaseFilePathName = getenv('ROOT_DIR') . "/database/".getenv('DATABASE_SQLITE_FILENAME');
        if (file_exists($databaseFilePathName)) {
            unlink($databaseFilePathName);
        }

        $sql = file_get_contents(getenv('ROOT_DIR') . "/database/".getenv('DATABASE_SQL_FILENAME'));
        $queries = array_filter(array_map('trim', explode(';', $sql)));

        $orm = FrugalContainer::getInstance()->get('orm');
        
        await($orm->execute("PRAGMA journal_mode = WAL;"));
        foreach ($queries as $query) {
            if ($query === '') continue;

            try {
                await($orm->execute($query));
            } catch (\Throwable $e) {
                echo "Erreur SQL : " . $e->getMessage() . "\n";
                echo "Requête : " . $query . "\n";
            }
        }

        $orm->close();

        return 0;
    }
}