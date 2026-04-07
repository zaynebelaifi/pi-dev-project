<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260407181736 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE delivery ADD fleet_car_id INT DEFAULT NULL, ADD license_plate VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE delivery ADD CONSTRAINT FK_3781EC1048CD51AF FOREIGN KEY (fleet_car_id) REFERENCES fleet_car (car_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3781EC10F5AA79D0 ON delivery (license_plate)');
        $this->addSql('CREATE INDEX IDX_3781EC1048CD51AF ON delivery (fleet_car_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE delivery DROP FOREIGN KEY FK_3781EC1048CD51AF');
        $this->addSql('DROP INDEX UNIQ_3781EC10F5AA79D0 ON delivery');
        $this->addSql('DROP INDEX IDX_3781EC1048CD51AF ON delivery');
        $this->addSql('ALTER TABLE delivery DROP fleet_car_id, DROP license_plate');
    }
}
