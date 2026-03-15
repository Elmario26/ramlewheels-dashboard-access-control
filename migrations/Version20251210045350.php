<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251210045350 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE document_activity_logs (id INT AUTO_INCREMENT NOT NULL, document_id INT NOT NULL, user_id INT DEFAULT NULL, action VARCHAR(50) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, INDEX IDX_D4E4B945C33F7837 (document_id), INDEX IDX_D4E4B945A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE document_activity_logs ADD CONSTRAINT FK_D4E4B945C33F7837 FOREIGN KEY (document_id) REFERENCES documents (id)');
        $this->addSql('ALTER TABLE document_activity_logs ADD CONSTRAINT FK_D4E4B945A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE documents ADD uploaded_by_id INT DEFAULT NULL, ADD updated_by_id INT DEFAULT NULL, ADD parent_document_id INT DEFAULT NULL, ADD category VARCHAR(50) DEFAULT NULL, ADD version INT DEFAULT 1, ADD is_latest_version TINYINT(1) DEFAULT 1, ADD updated_at DATETIME DEFAULT NULL');
        $this->addSql('UPDATE documents SET category = \'Vehicle Documents\', version = 1, is_latest_version = 1 WHERE category IS NULL');
        $this->addSql('ALTER TABLE documents ADD CONSTRAINT FK_A2B07288A2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE documents ADD CONSTRAINT FK_A2B07288896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE documents ADD CONSTRAINT FK_A2B07288A8136A47 FOREIGN KEY (parent_document_id) REFERENCES documents (id)');
        $this->addSql('CREATE INDEX IDX_A2B07288A2B28FE8 ON documents (uploaded_by_id)');
        $this->addSql('CREATE INDEX IDX_A2B07288896DBBDE ON documents (updated_by_id)');
        $this->addSql('CREATE INDEX IDX_A2B07288A8136A47 ON documents (parent_document_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document_activity_logs DROP FOREIGN KEY FK_D4E4B945C33F7837');
        $this->addSql('ALTER TABLE document_activity_logs DROP FOREIGN KEY FK_D4E4B945A76ED395');
        $this->addSql('DROP TABLE document_activity_logs');
        $this->addSql('ALTER TABLE documents DROP FOREIGN KEY FK_A2B07288A2B28FE8');
        $this->addSql('ALTER TABLE documents DROP FOREIGN KEY FK_A2B07288896DBBDE');
        $this->addSql('ALTER TABLE documents DROP FOREIGN KEY FK_A2B07288A8136A47');
        $this->addSql('DROP INDEX IDX_A2B07288A2B28FE8 ON documents');
        $this->addSql('DROP INDEX IDX_A2B07288896DBBDE ON documents');
        $this->addSql('DROP INDEX IDX_A2B07288A8136A47 ON documents');
        $this->addSql('ALTER TABLE documents DROP uploaded_by_id, DROP updated_by_id, DROP parent_document_id, DROP category, DROP version, DROP is_latest_version, DROP updated_at');
    }
}
