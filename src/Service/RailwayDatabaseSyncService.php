<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

/**
 * Copies all application tables from the local DB to a remote Railway MySQL database.
 * Intended for one-off use from APP_ENV=dev on your machine only.
 */
final class RailwayDatabaseSyncService
{
    /** Tables in FK-safe order (parents before children). */
    private const TABLE_ORDER = [
        'users',
        'user_verifications',
        'customer',
        'cars',
        'doctrine_migration_versions',
        'sales',
        'services',
        'documents',
        'document_activity_logs',
        'activity_logs',
        'test_drive_booking',
        'messenger_messages',
    ];

    public function __construct(
        private Connection $localConnection,
        private string $railwayDatabaseUrl = '',
    ) {}

    public function isConfigured(): bool
    {
        return trim($this->railwayDatabaseUrl) !== '';
    }

    /**
     * @return array{tables: array<string, int>, total: int}
     */
    public function sync(): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException(
                'RAILWAY_DATABASE_URL is not set. Add it to .env.local (copy MYSQL_URL from Railway → MySQL → Connect).'
            );
        }

        $remote = DriverManager::getConnection(['url' => $this->railwayDatabaseUrl]);

        $remote->executeStatement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach (self::TABLE_ORDER as $table) {
                if (!$this->tableExists($remote, $table)) {
                    continue;
                }
                $remote->executeStatement(sprintf('TRUNCATE TABLE `%s`', $table));
            }

            $counts = [];
            $total = 0;

            foreach (self::TABLE_ORDER as $table) {
                if (!$this->tableExists($this->localConnection, $table) || !$this->tableExists($remote, $table)) {
                    continue;
                }
                $copied = $this->copyTable($this->localConnection, $remote, $table);
                $counts[$table] = $copied;
                $total += $copied;
            }

            return ['tables' => $counts, 'total' => $total];
        } finally {
            $remote->executeStatement('SET FOREIGN_KEY_CHECKS=1');
            $remote->close();
        }
    }

    private function tableExists(Connection $connection, string $table): bool
    {
        $schema = $connection->createSchemaManager();

        return $schema->tablesExist([$table]);
    }

    private function copyTable(Connection $local, Connection $remote, string $table): int
    {
        $rows = $local->fetchAllAssociative(sprintf('SELECT * FROM `%s`', $table));

        if ($rows === []) {
            return 0;
        }

        $columns = array_keys($rows[0]);
        $columnList = implode(', ', array_map(static fn (string $c) => sprintf('`%s`', $c), $columns));
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $sql = sprintf('INSERT INTO `%s` (%s) VALUES (%s)', $table, $columnList, $placeholders);

        $count = 0;
        foreach ($rows as $row) {
            $remote->executeStatement($sql, array_values($row));
            ++$count;
        }

        return $count;
    }
}
