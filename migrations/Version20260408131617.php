<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260408131617 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change delivery_man_id foreign key to ON DELETE SET NULL';
    }

    public function up(Schema $schema): void
    {
        // Drop the existing foreign key
        $this->addSql('ALTER TABLE delivery DROP FOREIGN KEY FK_3781EC10FD128646');
        
        // Recreate the foreign key with ON DELETE SET NULL
        $this->addSql('ALTER TABLE delivery ADD CONSTRAINT FK_3781EC10FD128646 FOREIGN KEY (delivery_man_id) REFERENCES delivery_man (delivery_man_id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // Drop the modified foreign key
        $this->addSql('ALTER TABLE delivery DROP FOREIGN KEY FK_3781EC10FD128646');
        
        // Recreate the original foreign key with RESTRICT
        $this->addSql('ALTER TABLE delivery ADD CONSTRAINT FK_3781EC10FD128646 FOREIGN KEY (delivery_man_id) REFERENCES delivery_man (delivery_man_id)');
    }
}
