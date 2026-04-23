<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260421143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create webauthn_credential table for Face ID/WebAuthn credentials';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE webauthn_credential (id INT AUTO_INCREMENT NOT NULL, credential_id VARCHAR(512) NOT NULL, user_handle VARCHAR(128) NOT NULL, source_json LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX uniq_webauthn_credential_id (credential_id), INDEX idx_webauthn_user_handle (user_handle), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE webauthn_credential');
    }
}
