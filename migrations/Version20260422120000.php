<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add driver latitude/longitude to delivery table';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('delivery')) {
            return;
        }

        $table = $schema->getTable('delivery');

        if (!$table->hasColumn('driver_latitude')) {
            $this->addSql('ALTER TABLE delivery ADD driver_latitude DECIMAL(10,6) DEFAULT NULL');
        }

        if (!$table->hasColumn('driver_longitude')) {
            $this->addSql('ALTER TABLE delivery ADD driver_longitude DECIMAL(10,6) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('delivery')) {
            return;
        }

        $table = $schema->getTable('delivery');

        if ($table->hasColumn('driver_latitude')) {
            $this->addSql('ALTER TABLE delivery DROP COLUMN driver_latitude');
        }

        if ($table->hasColumn('driver_longitude')) {
            $this->addSql('ALTER TABLE delivery DROP COLUMN driver_longitude');
        }
    }
}
