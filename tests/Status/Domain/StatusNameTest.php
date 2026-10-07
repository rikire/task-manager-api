<?php

declare(strict_types=1);

namespace App\Tests\Status\Domain;

use App\Status\Domain\StatusName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * The machine name rule of ADR-0007 D3, held by the domain as the last line before the database
 * (design D3); the request DTO reports the same rule as a violation.
 */
#[Group('REQ-STATUS-create.invalid-values')]
final class StatusNameTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function validNames(): iterable
    {
        yield 'one letter' => ['a'];
        yield '50 characters' => [str_repeat('a', 50)];
        yield 'letters, digits and underscores' => ['code_review2'];
    }

    /** @return iterable<string, array{string}> */
    public static function invalidNames(): iterable
    {
        yield 'empty' => [''];
        yield '51 characters' => [str_repeat('a', 51)];
        yield 'uppercase' => ['Done'];
        yield 'space' => ['in progress'];
        yield 'leading digit' => ['1st'];
        yield 'leading underscore' => ['_new'];
        yield 'hyphen' => ['code-review'];
        yield 'Cyrillic' => ['готово'];
        yield 'final newline' => ["done\n"];
        yield 'leading space' => [' done'];
    }

    #[DataProvider('validNames')]
    public function testKeepsValidName(string $name): void
    {
        self::assertSame($name, new StatusName($name)->value);
    }

    #[DataProvider('invalidNames')]
    public function testRejectsInvalidName(string $name): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new StatusName($name);
    }
}
