<?php declare(strict_types=1);

namespace Tests;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Sassnowski\Arcanist\AssistantStep;
use Sassnowski\Arcanist\AbstractAssistant;
use Sassnowski\Arcanist\Renderer\FakeResponseRenderer;

class FakeResponseRendererTest extends TestCase
{
    /** @test */
    public function it_records_what_step_was_rendered(): void
    {
        $renderer = new FakeResponseRenderer();
        $assistant = m::mock(AbstractAssistant::class);

        $renderer->renderStep(
            new FakeStep($assistant, 1),
            $assistant
        );

        self::assertTrue($renderer->stepWasRendered(FakeStep::class));
        self::assertFalse($renderer->stepWasRendered(AnotherFakeStep::class));
    }

    /** @test */
    public function it_records_what_data_a_step_was_rendered_with(): void
    {
        $renderer = new FakeResponseRenderer();
        $assistant = m::mock(AbstractAssistant::class);

        $renderer->renderStep(
            new FakeStep($assistant, 1),
            $assistant,
            ['foo' => 'bar'],
        );

        self::assertTrue($renderer->stepWasRendered(FakeStep::class, ['foo' => 'bar']));
    }

    /** @test */
    public function it_records_redirects(): void
    {
        $assistant = m::mock(AbstractAssistant::class);
        $step = new FakeStep($assistant, 1);
        $renderer = new FakeResponseRenderer();

        $renderer->redirect($step, $assistant);

        self::assertTrue($renderer->didRedirectTo(FakeStep::class));
        self::assertFalse($renderer->didRedirectTo(AnotherFakeStep::class));
    }
}

class FakeStep extends AssistantStep
{
    public string $slug = 'step-slug';

    public function isComplete(): bool
    {
        return true;
    }
}

class AnotherFakeStep extends AssistantStep
{
    public function isComplete(): bool
    {
        return true;
    }
}
