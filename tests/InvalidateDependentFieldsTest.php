<?php declare(strict_types=1);

namespace Arcanist\Tests;

use Arcanist\Field;
use Arcanist\StepResult;
use Arcanist\WizardStep;
use Arcanist\AbstractWizard;
use Illuminate\Http\Request;

class InvalidateDependentFieldsTest extends WizardTestCase
{
    /** @test */
    public function it_unsets_a_single_dependent_field_if_the_field_it_depends_on_was_changed(): void
    {
        $repo = $this->createWizardRepository([
            '::normal-field-1::' => '::value-1::',
            '::dependent-field-1::' => '::value-2::',
        ], DependentStepWizard::class);
        $wizard = $this->createWizard(DependentStepWizard::class, repository: $repo);

        $wizard->update(Request::create('::uri::', 'POST', [
            '::normal-field-1::' => '::new-value::',
        ]), '1', 'regular-step');

        self::assertNull($repo->loadData($wizard)['::dependent-field-1::']);
    }

    /** @test */
    public function it_does_not_unset_a_dependent_field_if_the_field_it_depends_on_wasnt_changed(): void
    {
        $repo = $this->createWizardRepository([
            '::normal-field-1::' => '::value-1::',
            '::dependent-field-1::' => '::value-2::',
        ], DependentStepWizard::class);
        $wizard = $this->createWizard(DependentStepWizard::class, repository: $repo);

        $wizard->update(Request::create('::uri::', 'POST', [
            '::normal-field-1::' => '::value-1::',
        ]), '1', 'regular-step');

        self::assertEquals('::value-2::', $repo->loadData($wizard)['::dependent-field-1::']);
    }

    /** @test */
    public function it_unsets_a_dependent_field_if_one_of_its_dependencies_changed(): void
    {
        $repo = $this->createWizardRepository([
            '::normal-field-1::' => '::value-1::',
            '::dependent-field-1::' => '::value-2::',
            '::normal-field-2::' => '::value-3::',
        ], DependentStepWizard::class);
        $wizard = $this->createWizard(DependentStepWizard::class, repository: $repo);

        $wizard->update(Request::create('::uri::', 'POST', [
            '::normal-field-1::' => '::value-1::',
            '::normal-field-2::' => '::new-value::',
        ]), '1', 'regular-step');

        self::assertNull($repo->loadData($wizard)['::dependent-field-1::']);
    }

    /** @test */
    public function it_unsets_all_dependent_fields_if_a_common_dependency_changed(): void
    {
        $repo = $this->createWizardRepository([
            '::dependent-field-1::' => '::dependent-field-value::',
            '::dependent-field-2::' => '::dependent-field-2-value::',
            '::normal-field-2::' => '::normal-field-value::',
        ], MultiDependentStepWizard::class);
        $wizard = $this->createWizard(MultiDependentStepWizard::class, repository: $repo);

        $wizard->update(Request::create('::uri::', 'POST', [
            '::normal-field-2::' => '::new-value::',
        ]), '1', 'regular-step');

        self::assertNull($repo->loadData($wizard)['::dependent-field-1::']);
        self::assertNull($repo->loadData($wizard)['::dependent-field-2::']);
    }

    /** @test */
    public function it_unsets_all_dependent_fields_whose_dependency_was_changed(): void
    {
        $repo = $this->createWizardRepository([
            '::dependent-field-1::' => '::dependent-field-1-value::',
            '::dependent-field-3::' => '::dependent-field-3-value::',
            '::normal-field-1::' => '::normal-field-1-value::',
            '::normal-field-3::' => '::normal-field-3-value::',
        ], MultiDependentStepWizard::class);
        $wizard = $this->createWizard(MultiDependentStepWizard::class, repository: $repo);

        $wizard->update(Request::create('::uri::', 'POST', [
            '::normal-field-1::' => '::new-value::',
            '::normal-field-3::' => '::new-value::',
        ]), '1', 'regular-step');

        self::assertNull($repo->loadData($wizard)['::dependent-field-1::']);
        self::assertNull($repo->loadData($wizard)['::dependent-field-3::']);
    }

    /** @test */
    public function it_does_not_invalidate_dependent_fields_if_the_step_was_unsuccessful(): void
    {
        $repo = $this->createWizardRepository([
            '::normal-field-1::' => '::normal-field-1-value::',
            '::dependent-field-1::' => '::dependent-field-1-value::',
        ], FailingStepWizard::class);
        $wizard = $this->createWizard(FailingStepWizard::class, repository: $repo);

        $wizard->update(Request::create('::uri::', 'POST', [
            '::normal-field-1::' => '::new-value::',
        ]), '1', 'failing-step');

        self::assertEquals('::dependent-field-1-value::', $repo->loadData($wizard)['::dependent-field-1::']);
    }

    /** @test */
    public function it_marks_the_step_as_unfinished_if_any_of_its_fields_got_invalidated(): void
    {
        $repo = $this->createWizardRepository([
            '::normal-field-1::' => '::value-1::',
            '::dependent-field-1::' => '::value-2::',
            '_arcanist' => [
                '::step-with-dependent-field-slug::' => true,
            ]
        ], DependentStepWizard::class);
        $wizard = $this->createWizard(DependentStepWizard::class, repository: $repo);
        $wizard->setId(1);

        // Sanity check
        self::assertTrue(
            $repo->loadData($wizard)['_arcanist']['::step-with-dependent-field-slug::'] ?? false
        );

        $wizard->update(Request::create('::uri::', 'POST', [
            '::normal-field-1::' => '::new-value::',
        ]), '1', 'regular-step');

        self::assertNull(
            $repo->loadData($wizard)['_arcanist']['::step-with-dependent-field-slug::'] ?? null
        );
    }
}

class DependentStepWizard extends AbstractWizard
{
    protected array $steps = [
        RegularStep::class,
        StepWithDependentField::class,
    ];
}

class MultiDependentStepWizard extends AbstractWizard
{
    protected array $steps = [
        RegularStep::class,
        StepWithDependentField::class,
        AnotherStepWithDependentField::class,
    ];
}

class FailingStepWizard extends AbstractWizard
{
    protected array $steps = [
        FailingStep::class,
        StepWithDependentField::class,
    ];
}

class RegularStep extends WizardStep
{
    public string $slug = 'regular-step';

    public function isComplete(): bool
    {
        return true;
    }

    public function fields(): array
    {
        return [
            Field::make('::normal-field-1::'),
            Field::make('::normal-field-2::'),
            Field::make('::normal-field-3::'),
        ];
    }
}

class FailingStep extends WizardStep
{
    public string $slug = 'failing-step';

    public function isComplete(): bool
    {
        return false;
    }

    public function fields(): array
    {
        return [
            Field::make('::normal-field-1::')
        ];
    }

    protected function handle(Request $request, array $payload): StepResult
    {
        return $this->error('Whoops');
    }
}

class StepWithDependentField extends WizardStep
{
    public string $slug = '::step-with-dependent-field-slug::';

    public function isComplete(): bool
    {
        return $this->data('::dependent-field-1::') !== null;
    }

    public function fields(): array
    {
        return [
            Field::make('::dependent-field-1::')
                ->dependsOn('::normal-field-1::', '::normal-field-2::'),
        ];
    }
}

class AnotherStepWithDependentField extends WizardStep
{
    public function isComplete(): bool
    {
        return $this->data('::dependent-field-2::') !== null;
    }

    public function fields(): array
    {
        return [
            Field::make('::dependent-field-2::')
                ->dependsOn('::normal-field-2::'),

            Field::make('::dependent-field-3::')
                ->dependsOn('::normal-field-3::'),
        ];
    }
}
