<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260407185202 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional restaurant rating to delivery in an idempotent way';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on MySQL.');
        $this->addSql('ALTER TABLE delivery ADD COLUMN IF NOT EXISTS restaurant_rating INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on MySQL.');
        $this->addSql('ALTER TABLE delivery DROP COLUMN IF EXISTS restaurant_rating');
    }
}
