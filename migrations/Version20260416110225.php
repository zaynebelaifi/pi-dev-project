<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260416110225 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create ratings table for food donation event reviews';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ratings (rating_id INT AUTO_INCREMENT NOT NULL, donation_event_id INT NOT NULL, user_id INT DEFAULT NULL, event_rating INT NOT NULL, food_rating INT NOT NULL, comment VARCHAR(500) DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_CEB607C9BABCF7FB (donation_event_id), INDEX IDX_CEB607C9A76ED395 (user_id), PRIMARY KEY(rating_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ratings ADD CONSTRAINT FK_CEB607C9BABCF7FB FOREIGN KEY (donation_event_id) REFERENCES food_donation_event (donation_event_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ratings ADD CONSTRAINT FK_CEB607C9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ratings DROP FOREIGN KEY FK_CEB607C9BABCF7FB');
        $this->addSql('ALTER TABLE ratings DROP FOREIGN KEY FK_CEB607C9A76ED395');
        $this->addSql('DROP TABLE ratings');
    }
}
