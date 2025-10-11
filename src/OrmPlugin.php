<?php

namespace FrugalPhpPlugin\Orm;

use Frugal\Core\Plugins\AbstractPlugin;
use Frugal\Core\Services\FrugalContainer;
use FrugalPhpPlugin\Orm\Commands\UpdateSchemaCommand;
use FrugalPhpPlugin\Orm\Services\RepositoryLocator;
use FrugalPhpPlugin\Orm\Services\SqliteDatabase;

class OrmPlugin extends AbstractPlugin
{
    protected const PLUGIN_NAME = "Orm plugin";

    public function init() : void
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

    protected function registerServices(): void
    {
        $frugalContainer = FrugalContainer::getInstance();
        $frugalContainer->set('orm', fn() => new SqliteDatabase(getenv('DATABASE_FILEPATH')));
        $frugalContainer->set('repositoryLocator', fn() => new RepositoryLocator());

        // Force loading of database to improve perf since the first request
        $frugalContainer->get('orm');
    }
}