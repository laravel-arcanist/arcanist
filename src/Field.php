<?php declare(strict_types=1);

namespace Sassnowski\Arcanist;

class Field
{
    public array $rules = ['nullable'];
    public array $dependencies = [];

    private function __construct(public string $name)
    {
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
}
