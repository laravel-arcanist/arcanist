<?php declare(strict_types=1);

namespace Tests;

use Sassnowski\Arcanist\Field;

class FieldTest extends \PHPUnit\Framework\TestCase
{
    /** @test */
    public function it_can_create_a_new_field_with_a_name(): void
    {
        $field = Field::make('::name::');

        self::assertEquals('::name::', $field->name);
    }

    /** @test */
    public function it_can_add_validation_rules_to_a_field(): void
    {
        $field = Field::make('::name::')
            ->rules(['::something::']);

        self::assertEquals(['::something::'], $field->rules);
    }

    /** @test */
    public function it_is_nullable_by_default(): void
    {
        $field = Field::make('::name::');

        self::assertEquals(['nullable'], $field->rules);
    }

    /** @test */
    public function it_does_not_have_dependencies_by_default(): void
    {
        $field = Field::make('::name::');

        self::assertCount(0, $field->dependencies);
    }

    /** @test */
    public function it_can_specify_dependencies(): void
    {
        $field = Field::make('::name::')
            ->dependsOn('::field-1::', '::field-2::');

        self::assertEquals([
            '::field-1::',
            '::field-2::',
        ], $field->dependencies);
    }

    /** @test */
    public function it_should_not_change_if_none_of_its_dependencies_changed(): void
    {
        $field = Field::make('::dependent-field::')
            ->dependsOn('::field::');

        self::assertFalse($field->shouldInvalidate(['::another-field::']));
    }

    /** @test */
    public function it_should_invalidate_if_its_dependency_changed(): void
    {
        $field = Field::make('::dependent-field::')
            ->dependsOn('::field::');

        self::assertTrue($field->shouldInvalidate(['::field::']));
    }

    /** @test */
    public function it_should_invalidate_if_any_one_of_its_dependencies_changed(): void
    {
        $field = Field::make('::dependent-field::')
            ->dependsOn('::field-1::', '::field-2::');

        self::assertTrue($field->shouldInvalidate(['::field-2::']));
    }
}
