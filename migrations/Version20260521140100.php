<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260521140100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE test_drive_booking (id INT AUTO_INCREMENT NOT NULL, customer_id INT NOT NULL, car_id INT NOT NULL, approved_by_id INT DEFAULT NULL, requested_date_time DATETIME NOT NULL, status VARCHAR(50) NOT NULL, notes LONGTEXT DEFAULT NULL, staff_remarks LONGTEXT DEFAULT NULL, approved_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_18E6152E9395C3F3 (customer_id), INDEX IDX_18E6152EC3C6F69F (car_id), INDEX IDX_18E6152E2D234F6A (approved_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE test_drive_booking ADD CONSTRAINT FK_18E6152E9395C3F3 FOREIGN KEY (customer_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE test_drive_booking ADD CONSTRAINT FK_18E6152EC3C6F69F FOREIGN KEY (car_id) REFERENCES cars (id)');
        $this->addSql('ALTER TABLE test_drive_booking ADD CONSTRAINT FK_18E6152E2D234F6A FOREIGN KEY (approved_by_id) REFERENCES users (id)');
        $this->addSql('DROP TABLE lariosa');
        $this->addSql('ALTER TABLE documents CHANGE version version INT DEFAULT NULL, CHANGE is_latest_version is_latest_version TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE sales DROP FOREIGN KEY FK_6B817044B03A8386');
        $this->addSql('ALTER TABLE sales ADD CONSTRAINT FK_6B817044B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE lariosa (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, email VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, address VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE test_drive_booking DROP FOREIGN KEY FK_18E6152E9395C3F3');
        $this->addSql('ALTER TABLE test_drive_booking DROP FOREIGN KEY FK_18E6152EC3C6F69F');
        $this->addSql('ALTER TABLE test_drive_booking DROP FOREIGN KEY FK_18E6152E2D234F6A');
        $this->addSql('DROP TABLE test_drive_booking');
        $this->addSql('ALTER TABLE documents CHANGE version version INT DEFAULT 1, CHANGE is_latest_version is_latest_version TINYINT(1) DEFAULT 1');
        $this->addSql('ALTER TABLE sales DROP FOREIGN KEY FK_6B817044B03A8386');
        $this->addSql('ALTER TABLE sales ADD CONSTRAINT FK_6B817044B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE SET NULL');
    }
}
