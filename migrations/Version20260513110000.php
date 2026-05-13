<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260513110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Restore delivery man live location fields for fleet dashboard';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('delivery_man')) {
            return;
        }

        $table = $schema->getTable('delivery_man');
        $columns = [];

        if (!$table->hasColumn('latitude')) {
            $columns[] = 'ADD latitude NUMERIC(10, 6) DEFAULT NULL';
        }

        if (!$table->hasColumn('longitude')) {
            $columns[] = 'ADD longitude NUMERIC(10, 6) DEFAULT NULL';
        }

        if (!$table->hasColumn('last_location_update')) {
            $columns[] = 'ADD last_location_update DATETIME DEFAULT NULL';
        }

        if (!$table->hasColumn('is_available')) {
            $columns[] = 'ADD is_available TINYINT(1) DEFAULT 1 NOT NULL';
        }

        if ($columns) {
            $this->addSql('ALTER TABLE delivery_man ' . implode(', ', $columns));
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('delivery_man')) {
            return;
        }

        $table = $schema->getTable('delivery_man');
        $columns = [];

        if ($table->hasColumn('latitude')) {
            $columns[] = 'DROP latitude';
        }

        if ($table->hasColumn('longitude')) {
            $columns[] = 'DROP longitude';
        }

        if ($table->hasColumn('last_location_update')) {
            $columns[] = 'DROP last_location_update';
        }

        if ($table->hasColumn('is_available')) {
            $columns[] = 'DROP is_available';
        }

        if ($columns) {
            $this->addSql('ALTER TABLE delivery_man ' . implode(', ', $columns));
        }
    }
}
