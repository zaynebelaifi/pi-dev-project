<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260418123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize food donation event statuses and enforce default Scheduled';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE food_donation_event SET status = 'Scheduled' WHERE status IS NULL OR TRIM(status) = ''");
        $this->addSql("UPDATE food_donation_event SET status = 'Scheduled' WHERE UPPER(status) IN ('SCHEDULED', 'PENDING')");
        $this->addSql("UPDATE food_donation_event SET status = 'Ongoing' WHERE UPPER(status) = 'ONGOING'");
        $this->addSql("UPDATE food_donation_event SET status = 'Completed' WHERE UPPER(status) = 'COMPLETED'");
        $this->addSql("UPDATE food_donation_event SET status = 'Cancelled' WHERE UPPER(status) = 'CANCELLED'");
        $this->addSql("ALTER TABLE food_donation_event MODIFY status VARCHAR(50) NOT NULL DEFAULT 'Scheduled'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE food_donation_event SET status = 'SCHEDULED' WHERE status = 'Scheduled'");
        $this->addSql("UPDATE food_donation_event SET status = 'COMPLETED' WHERE status = 'Completed'");
        $this->addSql("UPDATE food_donation_event SET status = 'CANCELLED' WHERE status = 'Cancelled'");
        $this->addSql("ALTER TABLE food_donation_event MODIFY status VARCHAR(50) DEFAULT 'PENDING'");
    }
}
