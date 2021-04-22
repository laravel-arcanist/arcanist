<?php declare(strict_types=1);

namespace Sassnowski\Arcanist;

class Field
{
    private array $rules = [];

    private function __construct(private string $name)
    {
    }

    public static function make(string $name): Field
    {
        return new self($name);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function rules(array $rules): self
    {
        $this->rules = $rules;

        return $this;
    }

    public function getRules(): array
    {
        return $this->rules;
    }
}
