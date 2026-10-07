<?php

declare(strict_types=1);

namespace App\Tests\Status\Domain;

use App\Status\Domain\Status;
use App\Status\Domain\StatusId;
use App\Status\Domain\StatusName;
use App\Status\Domain\StatusTitle;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/** A status keeps what it was created with; its id is a lowercase UUID (ADR-0006, ADR-0007 D5). */
#[Group('REQ-STATUS-read.get')]
final class StatusTest extends TestCase
{
    public function testKeepsIdNameAndTitle(): void
    {
        $status = new Status(
            new StatusId('019b76da-a800-7000-8000-000000000002'),
            new StatusName('in_progress'),
            new StatusTitle('В работе'),
        );

        self::assertSame('019b76da-a800-7000-8000-000000000002', $status->id()->value);
        self::assertSame('in_progress', $status->name()->value);
        self::assertSame('В работе', $status->title()->value);
    }

    /** @return iterable<string, array{string}> */
    public static function malformedIds(): iterable
    {
        yield 'not a UUID' => ['abc'];
        yield 'empty' => [''];
        yield 'uppercase' => ['019B76DA-A800-7000-8000-000000000002'];
        yield 'final newline' => ["019b76da-a800-7000-8000-000000000002\n"];
    }

    #[DataProvider('malformedIds')]
    public function testRejectsMalformedId(string $id): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new StatusId($id);
    }
}
