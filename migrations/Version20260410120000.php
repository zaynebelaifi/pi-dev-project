<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260410120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add cart_items field to orders table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `orders` ADD cart_items LONGTEXT DEFAULT NULL AFTER total_amount');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `orders` DROP COLUMN cart_items');
    }
}
