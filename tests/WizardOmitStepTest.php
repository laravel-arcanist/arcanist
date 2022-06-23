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

use Arcanist\AbstractWizard;
use Arcanist\Action\ActionResult;
use Arcanist\Action\WizardAction;
use Arcanist\Arcanist;
use Arcanist\Contracts\WizardActionResolver;
use Arcanist\Field;
use Arcanist\NullAction;
use Arcanist\Renderer\FakeResponseRenderer;
use Arcanist\WizardStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Mockery as m;

class WizardOmitStepTest extends WizardTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
        $_SERVER['__onAfterComplete.called'] = 0;
        $_SERVER['__beforeDelete.called'] = 0;
        $_SERVER['__onAfterDelete.called'] = 0;

        OptionalStep::$omitCalled = 0;

        Arcanist::boot([
            MultiStepOmitWizard::class,
            MiddleOmittedStepWizard::class,
            FirstOmittedStepWizard::class,
            LastOmittedStepWizard::class,
            MultipleOmittedStepWizard::class,
        ]);
    }

    /**
     * Tests:
     * - 2 optional steps in a row still both get skipped.
     */
    public function testOmittedStepGetsSkipped(): void
    {
        $renderer = new FakeResponseRenderer();
        $wizard = $this->createWizard(MiddleOmittedStepWizard::class, renderer: $renderer);

        $wizard->update(Request::create('::uri::', 'POST', [
            'first_name' => '::first-name::',
            'last_name' => '::last-name::',
            'has_job' => true,
        ]), '1', 'step-name');

        self::assertTrue($renderer->didRedirectTo(AnotherStep::class));
    }

    public function testFirstOmittedStepGetsSkipped(): void
    {
        $renderer = new FakeResponseRenderer();
        $wizard = $this->createWizard(FirstOmittedStepWizard::class, renderer: $renderer);

        $wizard->create(new Request());

        self::assertTrue($renderer->stepWasRendered(FirstStep::class));
    }

    public function testLastOmittedStepSubmits(): void
    {
        // If the step is the last in the wizard, make sure it submits even if omitted.
        $actionSpy = m::spy(WizardAction::class);
        $actionSpy->allows('execute')->andReturns(ActionResult::success());
        $actionResolver = m::mock(WizardActionResolver::class);
        $actionResolver
            ->allows('resolveAction')
            ->with(NullAction::class)
            ->andReturn($actionSpy);
        $wizard = $this->createWizard(MultiStepOmitWizard::class, resolver: $actionResolver);

        $wizard->update(Request::create('::uri::', 'POST', [
            'first_name' => '::first-name::',
            'last_name' => '::last-name::',
            'has_job' => true,
        ]), '1', 'step-name');

        $actionSpy->shouldHaveReceived('execute')
            ->once();
    }

    public function testTwoOmittedStepsSkipToNextAvailable(): void
    {
        // If there's multiple steps in a row that are omitted, make sure the wizard directs to the next available step
        $renderer = new FakeResponseRenderer();
        $wizard = $this->createWizard(MultipleOmittedStepWizard::class, renderer: $renderer);

        $wizard->update(Request::create('::uri::', 'POST', [
            'first_name' => '::first-name::',
            'last_name' => '::last-name::',
            'has_job' => true,
        ]), '1', 'step-name');

        self::assertTrue($renderer->didRedirectTo(AnotherStep::class));
    }

    public function testOnlyComputesAvailableStepsOnce(): void
    {
        $wizard = $this->createWizard(
            MiddleOmittedStepWizard::class,
            renderer: new FakeResponseRenderer(),
        );

        $wizard->summary();
        $wizard->summary();

        self::assertSame(1, OptionalStep::$omitCalled);
    }
}

class MultiStepOmitWizard extends AbstractWizard
{
    protected array $steps = [
        FirstStep::class,
        OptionalStep::class,
    ];
}

class MiddleOmittedStepWizard extends AbstractWizard
{
    protected array $steps = [
        FirstStep::class,
        OptionalStep::class,
        AnotherStep::class,
    ];
}

class FirstOmittedStepWizard extends AbstractWizard
{
    protected array $steps = [
        OptionalStep::class,
        FirstStep::class,
    ];
}

class LastOmittedStepWizard extends AbstractWizard
{
    protected array $steps = [
        FirstStep::class,
        AnotherStep::class,
        OptionalStep::class,
    ];
}

class MultipleOmittedStepWizard extends AbstractWizard
{
    protected array $steps = [
        FirstStep::class,
        OptionalStep::class,
        AnotherOptionalStep::class,
        AnotherStep::class,
    ];
}

class FirstStep extends WizardStep
{
    public string $title = '::step-1-name::';
    public string $slug = 'step-name';

    public function fields(): array
    {
        return [
            Field::make('first_name')
                ->rules(['required']),

            Field::make('last_name')
                ->rules(['required']),

            Field::make('has_job')
                ->rules(['required', 'bool']),
        ];
    }

    public function viewData(Request $request): array
    {
        return [
            'first_name' => $this->data('first_name'),
            'last_name' => $this->data('last_name'),
            'has_job' => $this->data('has_job'),
        ];
    }

    public function isComplete(): bool
    {
        return true;
    }
}

class OptionalStep extends WizardStep
{
    public static int $omitCalled = 0;
    public string $title = '::step-2-name::';
    public string $slug = 'step-2-name';

    public function fields(): array
    {
        return [
            Field::make('company')
                ->rules(['required']),
        ];
    }

    public function viewData(Request $request): array
    {
        return [
            'company' => $this->data('company'),
        ];
    }

    public function omit(): bool
    {
        ++static::$omitCalled;

        return true;
    }
}

class AnotherOptionalStep extends WizardStep
{
    public string $title = '::step-4-name::';
    public string $slug = 'step-4-name';

    public function fields(): array
    {
        return [
            Field::make('xbox_gamertag')
                ->rules(['required']),
        ];
    }

    public function viewData(Request $request): array
    {
        return [
            'xbox_gamertag' => $this->data('xbox_gamertag'),
        ];
    }

    public function omit(): bool
    {
        return true;
    }
}

class AnotherStep extends WizardStep
{
    public string $title = '::step-3-name::';
    public string $slug = 'step-3-name';

    public function fields(): array
    {
        return [
            Field::make('pet_name')
                ->rules(['required']),
        ];
    }

    public function viewData(Request $request): array
    {
        return [
            'pet_name' => $this->data('pet_name'),
        ];
    }
}
