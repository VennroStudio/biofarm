<?php

declare(strict_types=1);

use Doctrine\DBAL\Connection;

function createOzonTestTables(Connection $db, array $tables): void
{
    if (getenv('APP_ENV') !== 'dev' || $db->getDatabase() !== 'biofarm'
        || ($db->getParams()['host'] ?? null) !== 'host.docker.internal' || (int)$db->getParams()['port'] !== 20001) {
        throw new RuntimeException('Run this test from local Docker with the Biofarm test-server database.');
    }

    foreach ($tables as $table) {
        $ddl = $db->fetchNumeric('SHOW CREATE TABLE ' . $table)[1];
        // InnoDB temporary tables cannot have foreign keys.
        $ddl = preg_replace('/,\n  CONSTRAINT [^\n]+/', '', $ddl);
        $db->executeStatement(str_replace('CREATE TABLE ', 'CREATE TEMPORARY TABLE ', $ddl));
    }
}

/** Both callers have already shadowed the catalog and state tables on this connection. */
function createStockTestSchema(Connection $db): void
{
    $class = App\Migrations\Version20261120180000::class;
    if ($db->fetchOne('SELECT 1 FROM migrations WHERE version=?', [$class])) {
        createOzonTestTables($db, ['ozon_stock_reservations']);
        return;
    }
    $migration = new $class($db, new Psr\Log\NullLogger());
    $migration->up(new Doctrine\DBAL\Schema\Schema());
    foreach ($migration->getSql() as $query) {
        $sql = $query->getStatement();
        if (str_starts_with($sql, 'CREATE TABLE ozon_stock_reservations')) {
            $sql = str_replace('CREATE TABLE ', 'CREATE TEMPORARY TABLE ', $sql);
        } elseif (!preg_match('/^ALTER TABLE ozon_catalog_(products|state) /', $sql)) {
            throw new RuntimeException('Unexpected Ozon stock fixture DDL.');
        }
        $db->executeStatement($sql);
    }
}
