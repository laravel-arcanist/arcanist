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
use Arcanist\WizardStep;
use Mockery as m;

class WizardStepTest extends TestCase
{
    private WizardStep $step;

    protected function setUp(): void
    {
        parent::setUp();

        $this->step = new class() extends WizardStep {
            public string $slug = '::step-slug::';
        };
    }

    public function testItConsidersAStepFinishedIfItWasSuccessfullySubmittedBefore(): void
    {
        $wizard = m::mock(AbstractWizard::class);
        $wizard->allows('data')->with('_arcanist.::step-slug::', false)->andReturnTrue();
        $this->step->init($wizard, 1);

        self::assertTrue($this->step->isComplete());
    }

    public function testItConsidersAStepUnfinishedIfItWasNeverSuccessfullySubmittedBefore(): void
    {
        $wizard = m::mock(AbstractWizard::class);
        $wizard->allows('data')->with('_arcanist.::step-slug::', false)->andReturnNull();
        $this->step->init($wizard, 1);

        self::assertFalse($this->step->isComplete());
    }
}
