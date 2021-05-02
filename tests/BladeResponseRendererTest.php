<?php declare(strict_types=1);

namespace Arcanist\Tests;

use Generator;
use function app;
use Mockery as m;
use function route;
use Arcanist\Arcanist;
use Arcanist\WizardStep;
use Arcanist\AbstractWizard;
use Illuminate\Contracts\View\View;
use Illuminate\Testing\TestResponse;
use Arcanist\Contracts\ResponseRenderer;
use Arcanist\Renderer\BladeResponseRenderer;

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

    /** @test */
    public function it_renders_the_correct_template_for_a_wizard_step(): void
    {
        $response = $this->renderer->renderStep(
            $this->step,
            $this->wizard,
            []
        );

        self::assertInstanceOf(View::class, $response);
        self::assertEquals("wizards.{$this->wizard::$slug}.{$this->step->slug}", $response->name());
    }

    /** @test */
    public function it_passes_along_the_view_data_to_the_view(): void
    {
        $response = $this->renderer->renderStep(
            $this->step,
            $this->wizard,
            ['::key::' => '::value::']
        );

        self::assertEquals(
            ['::key::' => '::value::'],
            $response->getData()['data']
        );
    }

    /** @test */
    public function it_provides_the_wizard_summary_to_every_view(): void
    {
        $response = $this->renderer->renderStep(
            $this->step,
            $this->wizard,
            []
        );

        self::assertEquals(
            ['::summary::'],
            $response->getData()['wizard']
        );
    }

    /**
     * @test
     * @dataProvider redirectToStepProvider
     */
    public function it_redirects_to_a_steps_view(callable $callRenderer): void
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
            ]
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
