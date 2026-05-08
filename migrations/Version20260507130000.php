<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds the missing FK constraint from reset_password_request.user_id → user.id.
 * The table was created by a previous migration attempt but without the FK.
 */
final class Version20260507130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add FK constraint on reset_password_request.user_id → user.id';
    }

    public function up(Schema $schema): void
    {
        // Only add the FK if it does not already exist
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
    }
}
