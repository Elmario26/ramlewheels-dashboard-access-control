<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260522140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add email verification columns to users table';
    }

    public function up(Schema $schema): void
    {
        $columns = array_map(
            static fn (string $name): string => strtolower($name),
            array_keys($this->connection->createSchemaManager()->listTableColumns('users'))
        );

        if (!in_array('is_verified', $columns, true)) {
            $this->addSql('ALTER TABLE users ADD is_verified TINYINT(1) DEFAULT NULL');
        }
        if (!in_array('verification_token', $columns, true)) {
            $this->addSql('ALTER TABLE users ADD verification_token VARCHAR(255) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $columns = array_map(
            static fn (string $name): string => strtolower($name),
            array_keys($this->connection->createSchemaManager()->listTableColumns('users'))
        );

        $drops = [];
        if (in_array('is_verified', $columns, true)) {
            $drops[] = 'DROP is_verified';
        }
        if (in_array('verification_token', $columns, true)) {
            $drops[] = 'DROP verification_token';
        }
        if ($drops !== []) {
            $this->addSql('ALTER TABLE users ' . implode(', ', $drops));
        }
    }
}
