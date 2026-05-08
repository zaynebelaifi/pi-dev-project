<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates the reset_password_request table used by the Forgot/Reset Password flow.
 *
 * Columns:
 *  - id           : auto-increment primary key
 *  - user_id      : FK → user.id (CASCADE DELETE — tokens are removed when the user is deleted)
 *  - hashed_token : SHA-256 hex digest of the real token (never store the plain token)
 *  - requested_at : when the request was created
 *  - expires_at   : when the token becomes invalid (1 hour after creation)
 */
final class Version20260507120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create reset_password_request table for the Forgot/Reset Password feature';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE reset_password_request (
                id           INT AUTO_INCREMENT NOT NULL,
                user_id      INT NOT NULL,
                hashed_token VARCHAR(100) NOT NULL,
                requested_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                expires_at   DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX IDX_7CE748AA76ED395 (user_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE reset_password_request
                ADD CONSTRAINT FK_7CE748AA76ED395
                FOREIGN KEY (user_id)
                REFERENCES `user` (id)
                ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('DROP TABLE reset_password_request');
    }
}
