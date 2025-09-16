<?php

namespace FrugalPhpPlugin\Orm\Commands;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\ORMSetup;
use FrugalPhpPlugin\Orm\Services\SqliteDatabase;
use ReflectionClass;

use function React\Async\await;

class UpdateSchemaCommand
{
    public static function run()
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([getenv('DATABASE_ENTITY_DIRECTORY')], true);
        $config->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER));

        $conn = DriverManager::getConnection([
            'driver' => getenv('DATABASE_DRIVER'),
            'path' => getenv('DATABASE_FILEPATH'),
        ], $config);

        $entityManager = new EntityManager($conn, $config);
        $schemaTool = new SchemaTool($entityManager);

        $classes = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropDatabase(); 
        $sql = $schemaTool->getCreateSchemaSql($classes);
        $sqlWithUnicityConstraints = self::addUnicityConstraints($sql);
        $sqliteDatabase = new SqliteDatabase(getenv('DATABASE_FILEPATH'));

        foreach($sqlWithUnicityConstraints as $sql) {
            await($sqliteDatabase->execute($sql));
        }

        $sqliteDatabase->close();

        return 0;
    }

    private static function addUnicityConstraints(array $sql): array
{
    $composerJsonPath = getenv('ROOT_DIR') . "/composer.json";
    $composerJson = json_decode(file_get_contents($composerJsonPath), true);

    $namespace = array_key_first($composerJson['autoload']['psr-4']);
    $psr4RootDir = getenv('ROOT_DIR')."/".current($composerJson['autoload']['psr-4']);
    $namespaceDir = substr(getenv('DATABASE_ENTITY_DIRECTORY'), strlen($psr4RootDir));
    $baseNamespace = $namespace.$namespaceDir;

    $files = array_filter(scandir(getenv("DATABASE_ENTITY_DIRECTORY")), fn($elm) => !in_array($elm, [".",".."]));

    foreach ($files as $file) {
        $fqcn = rtrim($baseNamespace, '\\') . '\\' . substr($file, 0, -4);

        $reflection = new \ReflectionClass($fqcn);
        $attributes = $reflection->getAttributes(\Doctrine\ORM\Mapping\Table::class);

        if (!$attributes) {
            throw new \RuntimeException("Table name not defined via #[Table(name: '...')] in class " . $fqcn);
        }

        /** @var \Doctrine\ORM\Mapping\Table $table */
        $table = $attributes[0]->newInstance();

        if (empty($table->uniqueConstraints)) {
            continue;
        }


        foreach ($table->uniqueConstraints as $uc) {
            $columns = implode(', ', $uc->columns);
            $sql[] = sprintf(
                'CREATE UNIQUE INDEX %s ON %s (%s)',
                $uc->name ?? ('uniq_' . implode('_', $uc->columns).random_int(1,1000)),
                $table->name,
                $columns
            );
        }
    }

    return $sql;
}
}