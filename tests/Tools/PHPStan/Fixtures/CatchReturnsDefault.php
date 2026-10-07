<?php

declare(strict_types=1);

namespace App\Tests\Tools\PHPStan\Fixtures;

final class CatchReturnsDefault
{
    public function returnsNull(): ?string
    {
        try {
            return $this->work();
        } catch (\RuntimeException $e) {
            return null;
        }
    }

    /** @return list<string>|bool|int|string */
    public function returnsOtherLiterals(int $kind): array|bool|int|string
    {
        try {
            return [$this->work()];
        } catch (\JsonException $e) {
            return false;
        } catch (\InvalidArgumentException $e) {
            return [];
        } catch (\OverflowException $e) {
            return 0;
        } catch (\UnderflowException $e) {
            return '';
        }
    }

    public function logsThenReturnsNull(): ?string
    {
        try {
            return $this->work();
        } catch (\RuntimeException $e) {
            $this->log($e);

            return null;
        }
    }

    public function rethrows(): string
    {
        try {
            return $this->work();
        } catch (\RuntimeException $e) {
            throw new \LogicException('Work failed.', 0, $e);
        }
    }

    public function returnsComputedValue(): string
    {
        try {
            return $this->work();
        } catch (\RuntimeException $e) {
            return $this->fallback($e);
        }
    }

    public function returnsLiteralOutsideCatch(): ?string
    {
        return null;
    }

    private function work(): string
    {
        return 'done';
    }

    private function log(\Throwable $e): void
    {
    }

    private function fallback(\Throwable $e): string
    {
        return $e->getMessage();
    }
}
