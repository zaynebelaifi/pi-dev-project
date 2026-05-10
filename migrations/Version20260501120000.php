<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260501120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add reminder_sent_at tracking for event registrations and food donation events';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('event_registration')) {
            $table = $schema->getTable('event_registration');

            if (!$table->hasColumn('reminder_sent_at')) {
                $this->addSql('ALTER TABLE event_registration ADD reminder_sent_at DATETIME DEFAULT NULL COMMENT \"(DC2Type:datetime_immutable)\"');
            }
        }

        if ($schema->hasTable('food_donation_event')) {
            $table = $schema->getTable('food_donation_event');

            if (!$table->hasColumn('reminder_sent_at')) {
                $this->addSql('ALTER TABLE food_donation_event ADD reminder_sent_at DATETIME DEFAULT NULL COMMENT \"(DC2Type:datetime_immutable)\"');
            }
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('event_registration')) {
            $table = $schema->getTable('event_registration');

            if ($table->hasColumn('reminder_sent_at')) {
                $this->addSql('ALTER TABLE event_registration DROP reminder_sent_at');
            }
        }

        if ($schema->hasTable('food_donation_event')) {
            $table = $schema->getTable('food_donation_event');

            if ($table->hasColumn('reminder_sent_at')) {
                $this->addSql('ALTER TABLE food_donation_event DROP reminder_sent_at');
            }
        }
    }
}
