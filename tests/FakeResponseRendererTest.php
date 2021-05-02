<?php declare(strict_types=1);

namespace Arcanist\Tests;

use Mockery as m;
use Arcanist\WizardStep;
use Arcanist\AbstractWizard;
use PHPUnit\Framework\TestCase;
use Arcanist\Renderer\FakeResponseRenderer;

class FakeResponseRendererTest extends TestCase
{
    /** @test */
    public function it_records_what_step_was_rendered(): void
    {
        $renderer = new FakeResponseRenderer();
        $wizard = m::mock(AbstractWizard::class);

        $renderer->renderStep(
            new FakeStep($wizard, '1'),
            $wizard
        );

        self::assertTrue($renderer->stepWasRendered(FakeStep::class));
        self::assertFalse($renderer->stepWasRendered(AnotherFakeStep::class));
    }

    /** @test */
    public function it_records_what_data_a_step_was_rendered_with(): void
    {
        $renderer = new FakeResponseRenderer();
        $wizard = m::mock(AbstractWizard::class);

        $renderer->renderStep(
            new FakeStep($wizard, '1'),
            $wizard,
            ['foo' => 'bar'],
        );

        self::assertTrue($renderer->stepWasRendered(FakeStep::class, ['foo' => 'bar']));
    }

    /** @test */
    public function it_records_redirects(): void
    {
        $wizard = m::mock(AbstractWizard::class);
        $step = new FakeStep($wizard, '1');
        $renderer = new FakeResponseRenderer();

        $renderer->redirect($step, $wizard);

        self::assertTrue($renderer->didRedirectTo(FakeStep::class));
        self::assertFalse($renderer->didRedirectTo(AnotherFakeStep::class));
        self::assertFalse($renderer->didRedirectWithError(FakeStep::class));
    }

    /** @test */
    public function it_records_redirects_with_errors(): void
    {
        $wizard = m::mock(AbstractWizard::class);
        $step = new FakeStep($wizard, '1');
        $renderer = new FakeResponseRenderer();

        $renderer->redirectWithError($step, $wizard, '::message::');

        self::assertTrue($renderer->didRedirectWithError(FakeStep::class, '::message::'));
        self::assertFalse($renderer->didRedirectTo(FakeStep::class));
    }
}

class FakeStep extends WizardStep
{
    public string $slug = 'step-slug';

    public function isComplete(): bool
    {
        return true;
    }
}

class AnotherFakeStep extends WizardStep
{
    public function isComplete(): bool
    {
        return true;
    }
}
