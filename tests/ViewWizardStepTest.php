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
use Arcanist\Exception\CannotUpdateStepException;
use Arcanist\Renderer\FakeResponseRenderer;
use Arcanist\WizardStep;
use Illuminate\Http\Request;

class ViewWizardStepTest extends WizardTestCase
{
    public function testItRedirectsToTheFirstIncompleteStepIfTryingSkipAhead(): void
    {
        $renderer = new FakeResponseRenderer();
        $wizard = $this->createWizard(IncompleteStepWizard::class, renderer: $renderer);

        $wizard->show(new Request(), '1', 'incomplete-step-2');

        self::assertTrue($renderer->didRedirectTo(IncompleteStep::class));
    }

    public function testItAllowsSkippingAheadIfTheTargetStepHasBeenCompletedPreviously(): void
    {
        $renderer = new FakeResponseRenderer();
        $wizard = $this->createWizard(IncompleteStepWizard::class, renderer: $renderer);

        $wizard->show(new Request(), '1', 'complete-step-2');

        self::assertTrue($renderer->stepWasRendered(AnotherCompleteStep::class));
    }

    public function testItDoesNotAllowUpdatingAnIncompleteStepIfThePreviousStepsHaveNotBeenCompletedYet(): void
    {
        $renderer = new FakeResponseRenderer();
        $wizard = $this->createWizard(IncompleteStepWizard::class, renderer: $renderer);

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
