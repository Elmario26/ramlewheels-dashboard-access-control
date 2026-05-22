<?php

namespace App\Command;

use App\Service\RailwaySeedTables;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-railway-seed',
    description: 'Import data/railway_seed.json (full database) into Railway MySQL',
)]
final class ImportRailwaySeedCommand extends Command
{
    public function __construct(
        private Connection $connection,
        private string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Replace existing Railway data (truncate tables first)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $path = $this->projectDir . '/data/railway_seed.json';
        $force = (bool) $input->getOption('force');

        if (!is_readable($path)) {
            $io->warning('No data/railway_seed.json — skip seed import.');

            return Command::SUCCESS;
        }

        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $tables = $this->resolveTables($payload);

        if ($tables === []) {
            $io->warning('Seed file has no table data.');

            return Command::SUCCESS;
        }

        $userCount = \count($tables['users'] ?? []);
        if ($userCount > 0 && !$force) {
            $existing = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM users');
            if ($existing > 0) {
                $io->note('Users already exist. Use --force or IMPORT_RAILWAY_SEED=force to replace all data.');

                return Command::SUCCESS;
            }
        }

        if ($force) {
            $io->warning('Replacing all seeded tables on this database.');
        }

        $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach (RailwaySeedTables::ORDER as $table) {
                if (!isset($tables[$table]) || !$this->tableExists($table)) {
                    continue;
                }
                if ($force) {
                    $this->connection->executeStatement(sprintf('TRUNCATE TABLE `%s`', $table));
                }
            }

            $counts = [];
            $total = 0;

            foreach (RailwaySeedTables::ORDER as $table) {
                if (!isset($tables[$table]) || !$this->tableExists($table)) {
                    continue;
                }
                $counts[$table] = $this->insertRows($table, $tables[$table]);
                $total += $counts[$table];
            }

            $io->success(sprintf('Imported %d rows.', $total));
            $io->table(['Table', 'Rows'], array_map(
                static fn (string $name, int $count) => [$name, (string) $count],
                array_keys($counts),
                array_values($counts),
            ));
        } finally {
            $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        }

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function resolveTables(array $payload): array
    {
        if (isset($payload['tables']) && \is_array($payload['tables'])) {
            return $payload['tables'];
        }

        return [];
    }

    private function tableExists(string $table): bool
    {
        return $this->connection->createSchemaManager()->tablesExist([$table]);
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function insertRows(string $table, array $rows): int
    {
        if ($rows === []) {
            return 0;
        }

        $columns = array_keys($rows[0]);
        $columnList = implode(', ', array_map(static fn (string $c) => sprintf('`%s`', $c), $columns));
        $placeholders = implode(', ', array_fill(0, \count($columns), '?'));
        $sql = sprintf('INSERT INTO `%s` (%s) VALUES (%s)', $table, $columnList, $placeholders);

        $count = 0;
        foreach ($rows as $row) {
            $this->connection->executeStatement($sql, array_values($row));
            ++$count;
        }

        return $count;
    }
}
