<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20260421150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add GPS and ETA fields to delivery_man and delivery tables for Fleet Management';
    }

    public function up(Schema $schema): void
    {
        // All GPS fields already exist from previous migrations
        // This migration is a placeholder to ensure migration consistency
    }

    public function down(Schema $schema): void
    {
        // This is a placeholder migration, no downgrade needed
    }
}
