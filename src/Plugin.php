<?php

namespace FrugalPhpPlugin\Orm;

use Frugal\Core\Plugins\AbstractPlugin;
use Frugal\Core\Services\FrugalContainer;
use FrugalPhpPlugin\Orm\Commands\UpdateSchemaCommand;
use FrugalPhpPlugin\Orm\Services\RepositoryLocator;
use FrugalPhpPlugin\Orm\Services\SqliteDatabase;

class Plugin extends AbstractPlugin
{
    protected const PLUGIN_NAME = "Orm plugin";

    public static function init() : void
    {
        parent::init();
        self::loadCommands([
            'schema:update' => UpdateSchemaCommand::class
        ]);

        self::checkEnvironmentVariables(
            [
                'DATABASE_ENTITY_DIRECTORY',
                'DATABASE_DRIVER',
                'DATABASE_FILEPATH'
            ]
            );
    }

    protected static function registerServices(): void
    {
        $frugalContainer = FrugalContainer::getInstance();
        $frugalContainer->set('orm', fn() => new SqliteDatabase(getenv('ROOT_DIR')."/".getenv('DATABASE_STORAGE_PATH')."/".getenv('DATABASE_SQLITE_FILENAME')));
        $frugalContainer->set('repositoryLocator', fn() => new RepositoryLocator());
    }
}