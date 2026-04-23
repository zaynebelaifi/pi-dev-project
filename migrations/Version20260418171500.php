<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260418171500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user.phone_number and food_donation_event.sms_reminder_sent for Twilio SMS notifications';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD phone_number VARCHAR(30) DEFAULT NULL');
        $this->addSql('ALTER TABLE food_donation_event ADD sms_reminder_sent TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP phone_number');
        $this->addSql('ALTER TABLE food_donation_event DROP sms_reminder_sent');
    }
}
