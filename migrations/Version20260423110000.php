<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user timestamps and explicit WebAuthn credential fields (user_id, public_key, counter)';
    }

    public function up(Schema $schema): void
    {
        // User audit timestamps required by authentication/account lifecycle tracking.
        $this->addSql('ALTER TABLE user ADD created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('UPDATE user SET created_at = NOW() WHERE created_at IS NULL');
        $this->addSql('UPDATE user SET updated_at = NOW() WHERE updated_at IS NULL');
        $this->addSql('ALTER TABLE user MODIFY created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', MODIFY updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');

        // Explicit WebAuthn fields for auditability and easier credential administration.
        $this->addSql('ALTER TABLE webauthn_credential ADD user_id BIGINT DEFAULT NULL, ADD public_key LONGTEXT DEFAULT NULL, ADD counter INT NOT NULL DEFAULT 0');
        $this->addSql('CREATE INDEX idx_webauthn_user_id ON webauthn_credential (user_id)');
        $this->addSql('ALTER TABLE webauthn_credential ADD CONSTRAINT FK_WEBAUTHN_USER_ID FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');

        // Backfill relation when user_handle stores numeric user IDs.
        $this->addSql('UPDATE webauthn_credential SET user_id = CAST(user_handle AS UNSIGNED) WHERE user_handle REGEXP "^[0-9]+$"');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE webauthn_credential DROP FOREIGN KEY FK_WEBAUTHN_USER_ID');
        $this->addSql('DROP INDEX idx_webauthn_user_id ON webauthn_credential');
        $this->addSql('ALTER TABLE webauthn_credential DROP user_id, DROP public_key, DROP counter');

        $this->addSql('ALTER TABLE user DROP created_at, DROP updated_at');
    }
}
