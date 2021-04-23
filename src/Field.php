<?php declare(strict_types=1);

namespace Sassnowski\Arcanist;

class Field
{
    public function __construct(
        public string $name,
        public array $rules = ['nullable'],
        public array $dependencies = []
    ) {
    }

    public static function make(string $name): Field
    {
        return new self($name);
    }

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

    public function shouldInvalidate(array $changedFieldNames): bool
    {
        return count(array_intersect($this->dependencies, $changedFieldNames)) > 0;
    }
}
