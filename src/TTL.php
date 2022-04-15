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

namespace Arcanist;

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
        if (0 > $value) {
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
