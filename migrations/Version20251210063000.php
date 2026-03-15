<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251210063000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add username field, make email optional for users, default existing users to username=email';
    }

    public function up(Schema $schema): void
    {
        // add username (temporarily nullable), backfill, then enforce not null + unique
        $this->addSql('ALTER TABLE users ADD username VARCHAR(180) DEFAULT NULL');
        $this->addSql('UPDATE users SET username = email WHERE username IS NULL');
        $this->addSql('ALTER TABLE users MODIFY username VARCHAR(180) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9F85E0677 ON users (username)');

        // make email optional (unique still enforced by DB)
        $this->addSql('ALTER TABLE users CHANGE email email VARCHAR(180) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users CHANGE email email VARCHAR(180) NOT NULL');
        $this->addSql('DROP INDEX UNIQ_1483A5E9F85E0677 ON users');
        $this->addSql('ALTER TABLE users DROP username');
    }
}

