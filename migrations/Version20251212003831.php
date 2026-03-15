<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251212003831 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sales ADD created_by_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_6B817044B03A8386 ON sales (created_by_id)');
        $this->addSql('ALTER TABLE sales ADD CONSTRAINT FK_6B817044B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activity_logs CHANGE entity_type entity_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE sales DROP FOREIGN KEY FK_6B817044B03A8386');
        $this->addSql('DROP INDEX IDX_6B817044B03A8386 ON sales');
        $this->addSql('ALTER TABLE sales DROP created_by_id');
    }
}
