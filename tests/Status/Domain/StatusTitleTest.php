<?php

declare(strict_types=1);

namespace App\Tests\Status\Domain;

use App\Status\Domain\StatusTitle;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * The title rule (ADR-0007 D3; owner, 2026-10-07): no control character anywhere, then trimmed of
 * whitespace and Unicode spaces, then 1 to 255 code points.
 */
#[Group('REQ-STATUS-create.invalid-values')]
final class StatusTitleTest extends TestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function acceptedTitles(): iterable
    {
        yield 'plain' => ['Ревью кода', 'Ревью кода'];
        yield 'spaces around' => ['  Ревью кода ', 'Ревью кода'];
        yield 'no-break spaces around' => ["\u{00A0}Ревью\u{00A0}", 'Ревью'];
        yield '255 Cyrillic code points' => [str_repeat('я', 255), str_repeat('я', 255)];
        yield '255 code points inside spaces' => [' '.str_repeat('я', 255).' ', str_repeat('я', 255)];
        yield 'format character (only Cc is rejected)' => ["Ре\u{200B}вью", "Ре\u{200B}вью"];
        yield 'HTML-looking text, not sanitised' => ['<script>', '<script>'];
    }

    /** @return iterable<string, array{string}> */
    public static function rejectedTitles(): iterable
    {
        yield 'empty' => [''];
        yield 'only spaces' => ['   '];
        yield 'only no-break spaces' => ["\u{00A0}\u{00A0}"];
        yield 'newline at the start, rejected before trimming' => ["\nРевью"];
        yield 'tab inside' => ["Ре\tвью"];
        yield 'NUL' => ["Ревью\u{0000}"];
        yield '256 code points' => [str_repeat('я', 256)];
    }

    #[DataProvider('acceptedTitles')]
    public function testStoresTrimmedTitle(string $title, string $stored): void
    {
        self::assertSame($stored, new StatusTitle($title)->value);
    }

    #[DataProvider('rejectedTitles')]
    public function testRejectsInvalidTitle(string $title): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new StatusTitle($title);
    }
}
