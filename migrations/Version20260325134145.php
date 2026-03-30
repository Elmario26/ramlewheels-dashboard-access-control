<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260325134145 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_verifications (id INT AUTO_INCREMENT NOT NULL, is_verified TINYINT(1) DEFAULT NULL, verification_token VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE activity_logs CHANGE entity_type entity_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE sales DROP FOREIGN KEY FK_6B817044B03A8386');
        $this->addSql('ALTER TABLE sales ADD CONSTRAINT FK_6B817044B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE users ADD is_verified TINYINT(1) DEFAULT NULL, ADD verification_token VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE user_verifications');
        $this->addSql('ALTER TABLE activity_logs CHANGE entity_type entity_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE sales DROP FOREIGN KEY FK_6B817044B03A8386');
        $this->addSql('ALTER TABLE sales ADD CONSTRAINT FK_6B817044B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('ALTER TABLE users DROP is_verified, DROP verification_token');
    }
}
