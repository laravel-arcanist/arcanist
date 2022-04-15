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

namespace Arcanist\Tests;

use Arcanist\Field;

class FieldTest extends TestCase
{
    public function testItCanCreateANewFieldWithAName(): void
    {
        $field = Field::make('::name::');

        self::assertEquals('::name::', $field->name);
    }

    public function testItCanAddValidationRulesToAField(): void
    {
        $field = Field::make('::name::')
            ->rules(['::something::']);

        self::assertEquals(['::something::'], $field->rules);
    }

    public function testItIsNullableByDefault(): void
    {
        $field = Field::make('::name::');

        self::assertEquals(['nullable'], $field->rules);
    }

    public function testItDoesNotHaveDependenciesByDefault(): void
    {
        $field = Field::make('::name::');

        self::assertCount(0, $field->dependencies);
    }

    public function testItCanSpecifyDependencies(): void
    {
        $field = Field::make('::name::')
            ->dependsOn('::field-1::', '::field-2::');

        self::assertEquals([
            '::field-1::',
            '::field-2::',
        ], $field->dependencies);
    }

    public function testItShouldNotChangeIfNoneOfItsDependenciesChanged(): void
    {
        $field = Field::make('::dependent-field::')
            ->dependsOn('::field::');

        self::assertFalse($field->shouldInvalidate(['::another-field::']));
    }

    public function testItShouldInvalidateIfItsDependencyChanged(): void
    {
        $field = Field::make('::dependent-field::')
            ->dependsOn('::field::');

        self::assertTrue($field->shouldInvalidate(['::field::']));
    }

    public function testItShouldInvalidateIfAnyOneOfItsDependenciesChanged(): void
    {
        $field = Field::make('::dependent-field::')
            ->dependsOn('::field-1::', '::field-2::');

        self::assertTrue($field->shouldInvalidate(['::field-2::']));
    }

    public function testItReturnsItsValueIfNoTransformationFunctionIsProvided(): void
    {
        $field = Field::make('::field::');

        $actual = $field->value('::value::');

        self::assertEquals('::value::', $actual);
    }

    public function testItAppliesTheRegisteredTransformationCallbackToTheValue(): void
    {
        $field = Field::make('::field::')
            ->transform(function ($value) {
                return '::mapped-value::';
            });

        self::assertEquals('::mapped-value::', $field->value('::value::'));
    }
}
