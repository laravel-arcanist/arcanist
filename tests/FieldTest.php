<?php declare(strict_types=1);

namespace Tests;

use Sassnowski\Arcanist\Field;

class FieldTest extends \PHPUnit\Framework\TestCase
{
    /** @test */
    public function it_can_create_a_new_field_with_a_name(): void
    {
        $field = Field::make('::name::');

        self::assertEquals('::name::', $field->getName());
    }

    /** @test */
    public function it_can_add_validation_rules_to_a_field(): void
    {
        $field = Field::make('::name::')
            ->rules(['::something::']);

        self::assertEquals(['::something::'], $field->getRules());
    }

    /** @test */
    public function it_does_not_have_validation_rules_by_default(): void
    {
        $field = Field::make('::name::');

        self::assertEmpty($field->getRules());
    }
}
