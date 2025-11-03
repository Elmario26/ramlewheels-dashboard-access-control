<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251009113250 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sales (id INT AUTO_INCREMENT NOT NULL, vehicle_id INT NOT NULL, customer_name VARCHAR(255) NOT NULL, customer_email VARCHAR(255) DEFAULT NULL, customer_phone VARCHAR(20) DEFAULT NULL, sale_price NUMERIC(10, 2) NOT NULL, down_payment NUMERIC(10, 2) DEFAULT NULL, financing_amount NUMERIC(10, 2) DEFAULT NULL, payment_method VARCHAR(50) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, sale_date DATETIME NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_6B817044545317D1 (vehicle_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sales ADD CONSTRAINT FK_6B817044545317D1 FOREIGN KEY (vehicle_id) REFERENCES cars (id)');
        $this->addSql('ALTER TABLE cars CHANGE images images JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sales DROP FOREIGN KEY FK_6B817044545317D1');
        $this->addSql('DROP TABLE sales');
        $this->addSql('ALTER TABLE cars CHANGE images images VARCHAR(255) NOT NULL');
    }
}
