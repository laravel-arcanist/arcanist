<?php declare(strict_types=1);

namespace Sassnowski\Arcanist;

use Carbon\Carbon;
use InvalidArgumentException;

final class TTL
{
    private function __construct(private int $value)
    {
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function fromSeconds(int $value): self
    {
        if ($value < 0) {
            throw new InvalidArgumentException();
        }

        return new self($value);
    }

    public function expiresAfter(): Carbon
    {
        return now()->sub('seconds', $this->value);
    }

    public function toSeconds(): int
    {
        return $this->value;
    }
}
