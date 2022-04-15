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
use Arcanist\Arcanist;
use Arcanist\Contracts\ResponseRenderer;
use Arcanist\Renderer\BladeResponseRenderer;
use Arcanist\Testing\ResponseRendererContractTests;
use Arcanist\WizardStep;
use Generator;
use Illuminate\Contracts\View\View;
use Illuminate\Testing\TestResponse;
use Mockery as m;
use function app;
use function route;

class BladeResponseRendererTest extends TestCase
{
    use ResponseRendererContractTests;
    private AbstractWizard $wizard;
    private WizardStep $step;
    private BladeResponseRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        config(['view.paths' => [__DIR__ . '/views']]);

        $wizard = m::mock(BladeTestWizard::class)->makePartial();
        $wizard->allows('summary')
            ->andReturns(['::summary::']);
        $this->wizard = $wizard->makePartial();
        $this->step = m::mock(BladeStep::class)->makePartial();
        $this->renderer = app(BladeResponseRenderer::class);

        Arcanist::boot([BladeTestWizard::class]);
    }

    public function testItRendersTheCorrectTemplateForAWizardStep(): void
    {
        $response = $this->renderer->renderStep(
            $this->step,
            $this->wizard,
            [],
        );

        self::assertInstanceOf(View::class, $response);
        self::assertEquals("wizards.{$this->wizard::$slug}.{$this->step->slug}", $response->name());
    }

    public function testItPassesAlongTheViewDataToTheView(): void
    {
        $response = $this->renderer->renderStep(
            $this->step,
            $this->wizard,
            ['::key::' => '::value::'],
        );

        self::assertEquals(
            ['::key::' => '::value::'],
            $response->getData()['step'],
        );
    }

    public function testItProvidesTheWizardSummaryToEveryView(): void
    {
        $response = $this->renderer->renderStep(
            $this->step,
            $this->wizard,
            [],
        );

        self::assertEquals(
            ['::summary::'],
            $response->getData()['wizard'],
        );
    }

    /**
     * @dataProvider redirectToStepProvider
     */
    public function testItRedirectsToAStepsView(callable $callRenderer): void
    {
        $this->wizard->setId(1);

        $response = new TestResponse($callRenderer($this->renderer, $this->wizard, $this->step));

        $response->assertRedirect(route('wizard.blade-wizard.show', [1, 'blade-step']));
    }

    public function redirectToStepProvider(): Generator
    {
        yield from [
            'redirect' => [
                function (BladeResponseRenderer $renderer, AbstractWizard $wizard, WizardStep $step) {
                    return $renderer->redirect($step, $wizard);
                },
            ],

            'redirectWithErrors' => [
                function (BladeResponseRenderer $renderer, AbstractWizard $wizard, WizardStep $step) {
                    return $renderer->redirectWithError($step, $wizard);
                },
            ],
        ];
    }

    protected function makeRenderer(): ResponseRenderer
    {
        return app(BladeResponseRenderer::class);
    }
}

class BladeTestWizard extends AbstractWizard
{
    public static string $slug = 'blade-wizard';
    protected array $steps = [
        BladeStep::class,
    ];
}

class BladeStep extends WizardStep
{
    public string $slug = 'blade-step';

    public function isComplete(): bool
    {
        return false;
    }
}

class SomeOtherStep extends WizardStep
{
    public function isComplete(): bool
    {
        return true;
    }
}
