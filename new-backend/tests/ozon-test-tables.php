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
