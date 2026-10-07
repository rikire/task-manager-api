<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Puts a project exception's message into the problem `detail` (ADR-0005, amended 2026-10-07). With debug off
 * the framework writes only the status text there ("Conflict"), so a client could not tell which domain rule
 * refused the request. framework.exceptions wraps a mapped exception in an HttpException, so the project
 * exception is found in the chain. Server errors (500 and above) keep the status text: their messages may
 * carry internals (RUL-SEC-boundaries).
 */
#[AsDecorator('serializer.normalizer.problem')]
final readonly class DomainProblemNormalizer implements NormalizerInterface
{
    private const string PROJECT_NAMESPACE = 'App\\';

    public function __construct(#[AutowireDecorated] private NormalizerInterface $inner)
    {
    }

    /** @return array<array-key, mixed> */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        $normalized = $this->inner->normalize($data, $format, $context);
        \assert(\is_array($normalized));

        if (!$data instanceof FlattenException || $data->getStatusCode() >= 500) {
            return $normalized;
        }

        $projectException = self::projectExceptionIn($data);
        if (null !== $projectException) {
            $normalized['detail'] = $projectException->getMessage();
        }

        return $normalized;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $this->inner->supportsNormalization($data, $format, $context);
    }

    public function getSupportedTypes(?string $format): array
    {
        return $this->inner->getSupportedTypes($format);
    }

    private static function projectExceptionIn(FlattenException $exception): ?FlattenException
    {
        for ($current = $exception; null !== $current; $current = $current->getPrevious()) {
            if (str_starts_with($current->getClass(), self::PROJECT_NAMESPACE)) {
                return $current;
            }
        }

        return null;
    }
}
