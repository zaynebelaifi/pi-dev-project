<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260421193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create donation_event_item pivot table and migrate existing food_donation_items data.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE donation_event_item (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, item_id INT NOT NULL, quantity INT NOT NULL, INDEX IDX_778D4F2671F7E88B (event_id), INDEX IDX_778D4F26126F525E (item_id), UNIQUE INDEX uniq_event_item_pair (event_id, item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE donation_event_item ADD CONSTRAINT FK_778D4F2671F7E88B FOREIGN KEY (event_id) REFERENCES food_donation_event (donation_event_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE donation_event_item ADD CONSTRAINT FK_778D4F26126F525E FOREIGN KEY (item_id) REFERENCES dish (id) ON DELETE CASCADE');

        $this->addSql('INSERT INTO donation_event_item (event_id, item_id, quantity) SELECT fdi.donation_event_id, fdi.item_id, fdi.quantity FROM food_donation_items fdi');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM food_donation_items');
        $this->addSql('INSERT INTO food_donation_items (donation_event_id, item_id, quantity) SELECT dei.event_id, dei.item_id, dei.quantity FROM donation_event_item dei');
        $this->addSql('DROP TABLE donation_event_item');
    }
}
