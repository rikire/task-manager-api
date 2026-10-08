<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncode;

/**
 * Writes non-ASCII text in JSON responses as UTF-8, not as \u escapes (REQ-API-json-utf8; ADR-0007, amended
 * 2026-10-08). AbstractController::json() passes its own encoding options, so the serializer's default context
 * never reaches success responses; the flag is added here to whatever options the caller passes, which keeps
 * the JSON_HEX_* escaping of success responses. Errors pass no options and get JsonEncode's own default.
 * Because the key is always set here, json_encode_options in framework.serializer.default_context would have no
 * effect: change ENCODE_DEFAULT instead.
 */
#[AsDecorator('serializer.encoder.json')]
final readonly class UnescapedUnicodeJsonEncoder implements EncoderInterface, DecoderInterface
{
    /** JsonEncode's default when the caller passes no options. */
    private const int ENCODE_DEFAULT = \JSON_PRESERVE_ZERO_FRACTION;

    public function __construct(#[AutowireDecorated] private EncoderInterface&DecoderInterface $inner)
    {
    }

    public function encode(mixed $data, string $format, array $context = []): string
    {
        // No options is the normal case for error responses, not a missing value.
        $options = $context[JsonEncode::OPTIONS] ?? self::ENCODE_DEFAULT;
        \assert(\is_int($options));
        $context[JsonEncode::OPTIONS] = $options | \JSON_UNESCAPED_UNICODE;

        return $this->inner->encode($data, $format, $context);
    }

    public function supportsEncoding(string $format): bool
    {
        return $this->inner->supportsEncoding($format);
    }

    public function decode(string $data, string $format, array $context = []): mixed
    {
        return $this->inner->decode($data, $format, $context);
    }

    public function supportsDecoding(string $format): bool
    {
        return $this->inner->supportsDecoding($format);
    }
}
