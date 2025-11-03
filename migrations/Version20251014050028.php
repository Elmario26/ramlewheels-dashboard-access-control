<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251014050028 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE services (id INT AUTO_INCREMENT NOT NULL, customer_id INT NOT NULL, vehicle_id INT DEFAULT NULL, service_type VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, cost NUMERIC(10, 2) NOT NULL, status VARCHAR(50) NOT NULL, service_date DATETIME NOT NULL, completion_date DATETIME DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_7332E1699395C3F3 (customer_id), INDEX IDX_7332E169545317D1 (vehicle_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE services ADD CONSTRAINT FK_7332E1699395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE services ADD CONSTRAINT FK_7332E169545317D1 FOREIGN KEY (vehicle_id) REFERENCES cars (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE services DROP FOREIGN KEY FK_7332E1699395C3F3');
        $this->addSql('ALTER TABLE services DROP FOREIGN KEY FK_7332E169545317D1');
        $this->addSql('DROP TABLE services');
    }
}
