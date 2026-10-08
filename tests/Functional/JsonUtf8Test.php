<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use PHPUnit\Framework\Attributes\Group;

/**
 * JSON text in UTF-8 (REQ-API-json-utf8; ADR-0007 amended). Asserts on the raw body: a decoded body cannot
 * tell `"Ж"` from `"\u0416"`.
 */
final class JsonUtf8Test extends ApiTestCase
{
    private const array JSON = ['CONTENT_TYPE' => 'application/json'];

    /** An escaped non-ASCII character as the requirement defines it; U+2028 and U+2029 stay escaped. */
    private const string ESCAPED_NON_ASCII = '/\\\\u(?!00[0-7][0-9a-f]|202[89])[0-9a-f]{4}/i';

    #[Group('REQ-API-json-utf8.success')]
    public function testWritesNonAsciiAsUtf8InSuccessResponses(): void
    {
        $created = (string) $this->sendValid('POST', '/api/statuses', '/api/statuses', $this->statusBody('code_review', 'Ревью кода 🚀'), self::JSON)->getContent();
        $list = (string) $this->sendValid('GET', '/api/statuses', '/api/statuses')->getContent();

        self::assertStringContainsString('Ревью кода 🚀', $created);
        foreach (['Ревью кода 🚀', 'Новая', 'В работе', 'Готово'] as $title) {
            self::assertStringContainsString($title, $list);
        }
        self::assertDoesNotMatchRegularExpression(self::ESCAPED_NON_ASCII, $created);
        self::assertDoesNotMatchRegularExpression(self::ESCAPED_NON_ASCII, $list);
    }

    #[Group('REQ-API-json-utf8.error')]
    public function testWritesNonAsciiAsUtf8InErrorResponses(): void
    {
        $response = $this->send('POST', '/api/statuses', $this->statusBody('Ревью', 'Ревью кода'), self::JSON, debug: false);

        self::assertProblem($response, 422);
        self::assertStringContainsString('Ревью', (string) $response->getContent());
        self::assertDoesNotMatchRegularExpression(self::ESCAPED_NON_ASCII, (string) $response->getContent());
    }

    #[Group('REQ-API-json-utf8.html-characters')]
    public function testKeepsHtmlCharactersEscaped(): void
    {
        $title = '<b>"Q&A'."'".'s"</b>';
        $response = $this->sendValid('POST', '/api/statuses', '/api/statuses', $this->statusBody('qa', $title), self::JSON);

        self::assertStringContainsString('"title":"\\u003Cb\\u003E\\u0022Q\\u0026A\\u0027s\\u0022\\u003C\\/b\\u003E"', (string) $response->getContent());
        self::assertSame($title, self::decode($response)['title']);
    }

    #[Group('REQ-API-json-utf8.line-separators')]
    public function testKeepsLineSeparatorsEscaped(): void
    {
        $response = $this->sendValid('POST', '/api/statuses', '/api/statuses', $this->statusBody('two_lines', "Первая\u{2028}вторая"), self::JSON);

        self::assertStringContainsString('"title":"Первая\\u2028вторая"', (string) $response->getContent());
    }

    private function statusBody(string $name, string $title): string
    {
        return json_encode(['name' => $name, 'title' => $title], \JSON_THROW_ON_ERROR);
    }
}
