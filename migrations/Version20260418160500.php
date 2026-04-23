<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260418160500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow comment-only feedback by making ratings.event_rating and ratings.food_rating nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ratings MODIFY event_rating INT DEFAULT NULL, MODIFY food_rating INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE ratings SET event_rating = 5 WHERE event_rating IS NULL');
        $this->addSql('UPDATE ratings SET food_rating = 5 WHERE food_rating IS NULL');
        $this->addSql('ALTER TABLE ratings MODIFY event_rating INT NOT NULL, MODIFY food_rating INT NOT NULL');
    }
}
