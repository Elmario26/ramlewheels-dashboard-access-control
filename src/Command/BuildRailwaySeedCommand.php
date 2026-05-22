<?php

namespace App\Command;

use App\Service\RailwaySeedTables;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'app:build-railway-seed',
    description: 'Export all local DB tables to data/railway_seed.json for Railway deploy',
)]
final class BuildRailwaySeedCommand extends Command
{
    public function __construct(
        private Connection $connection,
        private string $projectDir,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (($this->getApplication()?->getKernel()->getEnvironment() ?? '') !== 'dev') {
            $io->error('Run this only in dev (XAMPP): php bin/console app:build-railway-seed');

            return Command::FAILURE;
        }

        $tables = [];
        $totalRows = 0;

        foreach (RailwaySeedTables::ORDER as $table) {
            if (!$this->tableExists($table)) {
                continue;
            }
            $rows = $this->connection->fetchAllAssociative(sprintf('SELECT * FROM `%s`', $table));
            $tables[$table] = $rows;
            $totalRows += \count($rows);
        }

        $payload = [
            'version' => 2,
            'exportedAt' => (new \DateTime())->format('c'),
            'tables' => $tables,
        ];

        $path = $this->projectDir . '/data/railway_seed.json';
        (new Filesystem())->mkdir(\dirname($path));
        file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $io->success(sprintf('Wrote %s — %d rows across %d tables.', $path, $totalRows, \count($tables)));
        $io->table(['Table', 'Rows'], array_map(
            static fn (string $name, array $rows) => [$name, (string) \count($rows)],
            array_keys($tables),
            array_values($tables),
        ));
        $io->note('Commit data/railway_seed.json, push, then on Railway set IMPORT_RAILWAY_SEED=force and redeploy once.');

        return Command::SUCCESS;
    }

    private function tableExists(string $table): bool
    {
        return $this->connection->createSchemaManager()->tablesExist([$table]);
    }
}
