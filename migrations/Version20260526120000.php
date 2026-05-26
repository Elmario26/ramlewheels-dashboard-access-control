<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260526120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create service_booking table for mobile PMS/service appointment requests';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE service_booking (id INT AUTO_INCREMENT NOT NULL, customer_id INT NOT NULL, approved_by_id INT DEFAULT NULL, service_id VARCHAR(64) NOT NULL, service_name VARCHAR(255) NOT NULL, vehicle_description LONGTEXT NOT NULL, requested_date_time DATETIME NOT NULL, phone VARCHAR(32) NOT NULL, notes LONGTEXT DEFAULT NULL, status VARCHAR(50) NOT NULL, staff_remarks LONGTEXT DEFAULT NULL, approved_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_SERVICE_BOOKING_CUSTOMER (customer_id), INDEX IDX_SERVICE_BOOKING_APPROVED_BY (approved_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE service_booking ADD CONSTRAINT FK_SERVICE_BOOKING_CUSTOMER FOREIGN KEY (customer_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE service_booking ADD CONSTRAINT FK_SERVICE_BOOKING_APPROVED_BY FOREIGN KEY (approved_by_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE service_booking DROP FOREIGN KEY FK_SERVICE_BOOKING_CUSTOMER');
        $this->addSql('ALTER TABLE service_booking DROP FOREIGN KEY FK_SERVICE_BOOKING_APPROVED_BY');
        $this->addSql('DROP TABLE service_booking');
    }
}
