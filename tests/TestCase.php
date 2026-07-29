<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\Tests;

use RuntimeException;

abstract class TestCase
{
    protected function assertSame(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            $this->fail($message !== '' ? $message : sprintf(
                'Failed asserting that %s matches expected %s.',
                var_export($actual, true),
                var_export($expected, true)
            ));
        }
    }

    protected function assertTrue(bool $condition, string $message = ''): void
    {
        if (!$condition) {
            $this->fail($message !== '' ? $message : 'Failed asserting that condition is true.');
        }
    }

    protected function assertNotContains(string $needle, string $haystack, string $message = ''): void
    {
        if (str_contains($haystack, $needle)) {
            $this->fail($message !== '' ? $message : sprintf(
                'Failed asserting that [%s] does not contain [%s].',
                $haystack,
                $needle
            ));
        }
    }

    protected function assertContains(string $needle, string $haystack, string $message = ''): void
    {
        if (!str_contains($haystack, $needle)) {
            $this->fail($message !== '' ? $message : sprintf(
                'Failed asserting that [%s] contains [%s].',
                $haystack,
                $needle
            ));
        }
    }

    protected function fail(string $message): never
    {
        throw new RuntimeException($message);
    }
}
