<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Tasks (REQ-TASK-create; ADR-0007 D1, D2): each task references a status, and a status that tasks use cannot
 * be deleted — the foreign key refuses it (ON DELETE RESTRICT).
 */
final class Version20261007130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tasks referencing the status catalog';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE task (id UUID NOT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, status_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_task_status_id ON task (status_id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT fk_task_status FOREIGN KEY (status_id) REFERENCES status (id) ON DELETE RESTRICT NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE task');
    }
}
