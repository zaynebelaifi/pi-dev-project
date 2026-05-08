<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to change event_date column from DATE to DATETIME type
 * Preserves all existing data by setting time to 00:00:00
 */
final class Version20260416001436 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert FoodDonationEvent.event_date from DATE to DATETIME type';
    }

    public function up(Schema $schema): void
    {
        // Safely convert the event_date column from DATE to DATETIME
        // Existing dates will be preserved with time set to 00:00:00
        $this->addSql('ALTER TABLE food_donation_event MODIFY event_date DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Revert to DATE type if needed - this will truncate the time portion
        $this->addSql('ALTER TABLE food_donation_event MODIFY event_date DATE NOT NULL');
    }
}
