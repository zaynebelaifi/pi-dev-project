<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260416194000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create event_registration table for home page event signups';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE event_registration (id INT AUTO_INCREMENT NOT NULL, donation_event_id INT NOT NULL, user_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_A6A2D3B8837167D6 (donation_event_id), INDEX IDX_A6A2D3B8A76ED395 (user_id), UNIQUE INDEX uniq_event_user_registration (donation_event_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE event_registration ADD CONSTRAINT FK_A6A2D3B8837167D6 FOREIGN KEY (donation_event_id) REFERENCES food_donation_event (donation_event_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_registration ADD CONSTRAINT FK_A6A2D3B8A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE event_registration');
    }
}
