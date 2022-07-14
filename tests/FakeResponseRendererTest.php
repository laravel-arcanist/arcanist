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
use Arcanist\Renderer\FakeResponseRenderer;
use Arcanist\WizardStep;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class FakeResponseRendererTest extends TestCase
{
    public function testItRecordsWhatStepWasRendered(): void
    {
        $renderer = new FakeResponseRenderer();
        $wizard = m::mock(AbstractWizard::class);

        $renderer->renderStep(
            new FakeStep(),
            $wizard,
        );

        self::assertTrue($renderer->stepWasRendered(FakeStep::class));
        self::assertFalse($renderer->stepWasRendered(AnotherFakeStep::class));
    }

    public function testItRecordsWhatDataAStepWasRenderedWith(): void
    {
        $renderer = new FakeResponseRenderer();
        $wizard = m::mock(AbstractWizard::class);

        $renderer->renderStep(
            new FakeStep(),
            $wizard,
            ['foo' => 'bar'],
        );

        self::assertTrue($renderer->stepWasRendered(FakeStep::class, ['foo' => 'bar']));
    }

    public function testItRecordsRedirects(): void
    {
        $wizard = m::mock(AbstractWizard::class);
        $step = new FakeStep();
        $renderer = new FakeResponseRenderer();

        $renderer->redirect($step, $wizard);

        self::assertTrue($renderer->didRedirectTo(FakeStep::class));
        self::assertFalse($renderer->didRedirectTo(AnotherFakeStep::class));
        self::assertFalse($renderer->didRedirectWithError(FakeStep::class));
    }

    public function testItRecordsRedirectsWithErrors(): void
    {
        $wizard = m::mock(AbstractWizard::class);
        $step = new FakeStep();
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
