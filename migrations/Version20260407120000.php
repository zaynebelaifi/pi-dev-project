<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260407120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add cart_items and order_total fields to delivery entity for client checkout';
    }

    public function up(Schema $schema): void
    {
        $deliveryTable = $schema->getTable('delivery');
        
        if (!$deliveryTable->hasColumn('cart_items')) {
            $this->addSql('ALTER TABLE delivery ADD cart_items LONGTEXT DEFAULT NULL');
        }
        
        if (!$deliveryTable->hasColumn('order_total')) {
            $this->addSql('ALTER TABLE delivery ADD order_total NUMERIC(10, 2) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE delivery DROP cart_items, DROP order_total');
    }
}
