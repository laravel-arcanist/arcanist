<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/laravel-arcanist/arcanist
 */

namespace Arcanist\Action;

class ActionResult
{
    /**
     * @param array<string, mixed> $payload
     */
    private function __construct(
        private bool $successful,
        private array $payload,
        private ?string $errorMessage = null,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function success(array $payload = []): self
    {
        return new self(true, $payload);
    }

    public static function failed(?string $message = null): self
    {
        return new self(false, [], $message);
    }

    public function successful(): bool
    {
        return $this->successful;
    }

    public function get(string $key): mixed
    {
        return $this->payload[$key];
    }

    public function error(): ?string
    {
        return $this->errorMessage;
    }
}
