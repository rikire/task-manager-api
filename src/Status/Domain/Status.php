<?php

declare(strict_types=1);

namespace App\Status\Domain;

/**
 * A status of the catalog (assignment: id, name, title). Fields are stored as strings so the XML mapping
 * needs no custom types; the value objects guard them on the way out (and on the way in once statuses are
 * created, group 3). Not final: Doctrine creates lazy proxies for the Task → Status relation (ADR-0006).
 */
class Status
{
    private string $id;
    private string $name;
    private string $title;

    public function __construct(StatusId $id, StatusName $name, StatusTitle $title)
    {
        $this->id = $id->value;
        $this->name = $name->value;
        $this->title = $title->value;
    }

    public function id(): StatusId
    {
        return new StatusId($this->id);
    }

    public function name(): StatusName
    {
        return new StatusName($this->name);
    }

    public function title(): StatusTitle
    {
        return new StatusTitle($this->title);
    }
}
