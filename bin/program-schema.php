<?php

declare(strict_types=1);
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

/** Local additive schema bootstrap. Deployment migrations are generated/reviewed by the developer. */
require dirname(__DIR__) . '/vendor/autoload.php';
$container = require dirname(__DIR__) . '/config/container.php';
$em = $container->get(EntityManagerInterface::class);
$manager = $em->getConnection()->createSchemaManager();
$existing = $manager->listTableNames();
$all = array_values(array_filter($em->getMetadataFactory()->getAllMetadata(), static fn ($m): bool => (
    str_starts_with($m->name, 'App\Modules\Program\Entity\\') || str_starts_with($m->name, 'App\Modules\Payment\Entity\\') || str_starts_with($m->name, 'App\Modules\PartnerOffer\Entity\\')
)));
$metadata = array_values(array_filter($all, static fn ($m): bool => !in_array($m->getTableName(), $existing, true)));
$sql = new SchemaTool($em)->getCreateSchemaSql($metadata);
$desired = new SchemaTool($em)->getSchemaFromMetadata($all);
foreach ($desired->getTables() as $table) {
    if (!in_array($table->getName(), $existing, true)) {
        continue;
    }
    $actual = $manager->introspectTable($table->getName());
    foreach ($table->getIndexes() as $index) {
        if (!$actual->hasIndex($index->getName())) {
            $sql[] = $em->getConnection()->getDatabasePlatform()->getCreateIndexSQL($index, $table->getName());
        }
    }
}
foreach ($sql as $statement) {
    echo $statement . ";\n";
}
if (in_array('--apply-local', $argv, true)) {
    if (App\Components\env('APP_ENV', 'prod') !== 'dev') {
        throw new RuntimeException('Local schema bootstrap requires APP_ENV=dev');
    }
    foreach ($sql as $statement) {
        $em->getConnection()->executeStatement($statement);
    }
    echo 'Applied additive schema statements: ' . count($sql) . "\n";
}
