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

use Illuminate\Contracts\Validation\Rule;

class Field
{
    /**
     * @var null|callable(mixed): mixed
     */
    private $transformationCallback;

    /**
     * @param array<int, Rule|string>  $rules
     * @param array<array-key, string> $dependencies
     */
    public function __construct(
        public string $name,
        public array $rules = ['nullable'],
        public array $dependencies = [],
    ) {
    }

    public static function make(string $name): static
    {
        /** @phpstan-ignore-next-line */
        return new static($name);
    }

    /**
     * @param array<int, Rule|string> $rules
     */
    public function rules(array $rules): self
    {
        $this->rules = $rules;

        return $this;
    }

    public function dependsOn(string ...$fields): self
    {
        $this->dependencies = $fields;

        return $this;
    }

    /**
     * @param array<int, string> $changedFieldNames
     */
    public function shouldInvalidate(array $changedFieldNames): bool
    {
        return \count(\array_intersect($this->dependencies, $changedFieldNames)) > 0;
    }

    public function value(mixed $value): mixed
    {
        $callback = $this->transformationCallback ?: fn ($val) => $val;

        return $callback($value);
    }

    public function transform(callable $callback): self
    {
        $this->transformationCallback = $callback;

        return $this;
    }
}
