<?php

namespace FrugalPhpPlugin\Orm;

use Exception;
use Frugal\Core\Plugins\AbstractPlugin;
use Frugal\Core\Services\FrugalContainer;
use FrugalPhpPlugin\Orm\Commands\Database\ResetDatabase;
use FrugalPhpPlugin\Orm\Services\SqliteDatabase;

class Plugin extends AbstractPlugin
{
    protected const PLUGIN_NAME = "Orm plugin";

    public static function init() : void
    {
        parent::init();
        self::loadCommands(['resetDatabase' => ResetDatabase::class]);
        self::checkEnvironmentVariables(
            [
                'DATABASE_STORAGE_PATH',
                'DATABASE_SQL_FILENAME',
                'DATABASE_SQLITE_FILENAME'
            ]
            );

        // Check si tout est en place
        if(!file_exists(getenv('ROOT_DIR')."/".getenv('DATABASE_STORAGE_PATH'))) {
            throw new Exception("Le répertoire de stockage de la base de donnée n'est pas accessible");
        }
    }

    protected static function registerServices(): void
    {
        FrugalContainer::getInstance()->set('orm', fn() => new SqliteDatabase(getenv('ROOT_DIR')."/".getenv('DATABASE_STORAGE_PATH')."/".getenv('DATABASE_SQLITE_FILENAME')));
    }
}