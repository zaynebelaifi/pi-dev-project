<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260513104500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add password reset codes table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE password_reset_code (
            id INT AUTO_INCREMENT NOT NULL,
            email VARCHAR(180) NOT NULL,
            code_hash VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            used_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            attempts INT DEFAULT 0 NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX idx_password_reset_email (email),
            INDEX idx_password_reset_created_at (created_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE password_reset_code');
    }
}
