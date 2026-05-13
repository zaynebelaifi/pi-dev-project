<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260513103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure the delivery_reviews table exists for AI verified testimonials.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS delivery_reviews (
            id INT AUTO_INCREMENT NOT NULL,
            order_id VARCHAR(64) NOT NULL,
            customer_name VARCHAR(255) NOT NULL,
            customer_email VARCHAR(255) NOT NULL,
            review_text LONGTEXT NOT NULL,
            rating INT DEFAULT NULL,
            sentiment VARCHAR(16) DEFAULT NULL,
            confidence DOUBLE PRECISION DEFAULT NULL,
            summary VARCHAR(255) DEFAULT NULL,
            routed_to VARCHAR(32) DEFAULT NULL,
            support_ticket LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS delivery_reviews');
    }
}
