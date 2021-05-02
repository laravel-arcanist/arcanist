<?php declare(strict_types=1);

namespace Arcanist\Tests;

use Mockery as m;
use Arcanist\WizardStep;
use Arcanist\AbstractWizard;

class WizardStepTest extends TestCase
{
    private WizardStep $step;

    protected function setUp(): void
    {
        parent::setUp();

        $this->step = new class extends WizardStep {
            public string $slug = '::step-slug::';
        };
    }

    /** @test */
    public function it_considers_a_step_finished_if_it_was_successfully_submitted_before(): void
    {
        $wizard = m::mock(AbstractWizard::class);
        $wizard->allows('data')->with('_arcanist.::step-slug::', false)->andReturnTrue();
        $this->step->init($wizard, 1);

        self::assertTrue($this->step->isComplete());
    }

    /** @test */
    public function it_considers_a_step_unfinished_if_it_was_never_successfully_submitted_before(): void
    {
        $wizard = m::mock(AbstractWizard::class);
        $wizard->allows('data')->with('_arcanist.::step-slug::', false)->andReturnNull();
        $this->step->init($wizard, 1);

        self::assertFalse($this->step->isComplete());
    }
}
