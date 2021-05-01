<?php declare(strict_types=1);

namespace Arcanist\Action;

class ActionResult
{
    private function __construct(
        private bool $successful,
        private array $payload,
        private ?string $errorMessage = null
    ) {
    }

    public static function success(array $payload = []): ActionResult
    {
        return new self(true, $payload);
    }

    public static function failed(?string $message = null): ActionResult
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
