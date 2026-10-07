<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Status catalog and its initial statuses (REQ-STATUS-initial; ADR-0007 D1, D3; design D5). The seed ids are
 * fixed UUID v7 values from 2026-01-01T00:00:00Z, so they sort before every id generated at run time and list
 * as new, in_progress, done.
 */
final class Version20261007120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Status catalog with the statuses new, in_progress, done';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE status (id UUID NOT NULL, name VARCHAR(50) NOT NULL, title VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_status_name ON status (name)');
        $this->addSql(<<<'SQL'
            INSERT INTO status (id, name, title) VALUES
                ('019b76da-a800-7000-8000-000000000001', 'new', 'Новая'),
                ('019b76da-a800-7000-8000-000000000002', 'in_progress', 'В работе'),
                ('019b76da-a800-7000-8000-000000000003', 'done', 'Готово')
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE status');
    }
}
