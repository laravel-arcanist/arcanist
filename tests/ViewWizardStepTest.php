<?php declare(strict_types=1);

namespace Tests;

use Illuminate\Http\Request;
use Sassnowski\Arcanist\WizardStep;
use Sassnowski\Arcanist\AbstractWizard;
use Sassnowski\Arcanist\Renderer\FakeResponseRenderer;
use Sassnowski\Arcanist\Exception\CannotUpdateStepException;

class ViewWizardStepTest extends WizardTestCase
{
    /** @test */
    public function it_redirects_to_the_first_incomplete_step_if_trying_skip_ahead(): void
    {
        $renderer = new FakeResponseRenderer();
        $wizard = $this->createWizard(IncompleteStepWizard::class, renderer:  $renderer);

        $wizard->show(new Request(), '1', 'incomplete-step-2');

        self::assertTrue($renderer->didRedirectTo(IncompleteStep::class));
    }

    /** @test */
    public function it_allows_skipping_ahead_if_the_target_step_has_been_completed_previously(): void
    {
        $renderer = new FakeResponseRenderer();
        $wizard = $this->createWizard(IncompleteStepWizard::class, renderer:  $renderer);

        $wizard->show(new Request(), '1', 'complete-step-2');

        self::assertTrue($renderer->stepWasRendered(AnotherCompleteStep::class));
    }

    /** @test */
    public function it_does_not_allow_updating_an_incomplete_step_if_the_previous_steps_have_not_been_completed_yet(): void
    {
        $renderer = new FakeResponseRenderer();
        $wizard = $this->createWizard(IncompleteStepWizard::class, renderer:  $renderer);

        $this->expectException(CannotUpdateStepException::class);

        $wizard->update(new Request(), '1', 'incomplete-step-2');
    }
}

class IncompleteStepWizard extends AbstractWizard
{
    protected array $steps = [
        CompleteStep::class,
        IncompleteStep::class,
        AnotherCompleteStep::class,
        AnotherIncompleteStep::class,
    ];
}

class CompleteStep extends WizardStep
{
    public string $slug = 'complete-step-1';

    public function isComplete(): bool
    {
        return true;
    }
}

class IncompleteStep extends WizardStep
{
    public string $slug = 'incomplete-step-1';

    public function isComplete(): bool
    {
        return false;
    }
}

class AnotherCompleteStep extends WizardStep
{
    public string $slug = 'complete-step-2';

    public function isComplete(): bool
    {
        return true;
    }
}

class AnotherIncompleteStep extends WizardStep
{
    public string $slug = 'incomplete-step-2';

    public function isComplete(): bool
    {
        return false;
    }
}
