<?php declare(strict_types=1);

namespace Arcanist\Tests;

use Spatie\Snapshots\MatchesSnapshots;

class WizardStepMakeCommandTest extends TestCase
{
    use MatchesSnapshots;

    /** @test */
    public function it_prefills_the_steps_title(): void
    {
        $this->artisan('make:wizard-step Step1 TestWizard');

        $this->assertMatchesFileSnapshot(app_path('Wizards/TestWizard/Steps/Step1.php'));
    }

    /** @test */
    public function it_prefills_the_steps_slug(): void
    {
        $this->artisan('make:wizard-step Step1 TestWizard');

        $this->assertMatchesFileSnapshot(app_path('Wizards/TestWizard/Steps/Step1.php'));
    }
}
