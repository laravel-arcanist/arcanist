<?php declare(strict_types=1);

namespace Arcanist\Tests;

use Generator;
use function app_path;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Spatie\Snapshots\MatchesSnapshots;

class WizardMakeCommandTest extends TestCase
{
    use MatchesSnapshots;

    private string $wizardName;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wizardName = Str::random();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(app_path('Wizards'));

        parent::tearDown();
    }

    /**
     * @test
     * @dataProvider stepCommandInvocationProvider
     */
    public function it_creates_steps_if_the_step_option_was_provided(array $steps): void
    {
        $option = implode(',', $steps);

        $this
            ->withoutExceptionHandling()
            ->artisan('make:wizard ' . $this->wizardName . ' --steps=' . $option);

        foreach ($steps as $step) {
            $this->assertTrue(File::exists(
                app_path('Wizards/' . $this->wizardName . '/Steps/' . $step . '.php')
            ));
        }
    }

    public function stepCommandInvocationProvider(): Generator
    {
        yield from [
            [['Step1']],
            [['Step1', 'Step2']],
        ];
    }

    /** @test */
    public function it_does_not_create_steps_if_the_option_isnt_provided(): void
    {
        $this->artisan('make:wizard ' . $this->wizardName);

        $this->assertFalse(File::exists(app_path('Wizards/' . $this->wizardName . '/Steps')));
    }

    /**
     * @test
     * @dataProvider emptyStepNameProvider
     */
    public function it_does_not_create_steps_if_no_step_names_are_provided(string $option): void
    {
        $this->artisan('make:wizard ' . $this->wizardName . ' ' . $option);

        $this->assertFalse(File::exists(app_path('Wizards/' . $this->wizardName . '/Steps')));
    }

    public function emptyStepNameProvider(): Generator
    {
        yield from [
            ['--steps'],
            ['--steps='],
        ];
    }

    /** @test */
    public function it_prefills_the_generated_wizards_title_property(): void
    {
        $this->artisan('make:wizard TestWizard');

        $this->assertMatchesFileSnapshot(app_path('Wizards/TestWizard/TestWizard.php'));
    }

    /** @test */
    public function it_prefills_the_generated_wizards_slug_property(): void
    {
        $this->artisan('make:wizard TestWizard');

        $this->assertMatchesFileSnapshot(app_path('Wizards/TestWizard/TestWizard.php'));
    }

    /** @test */
    public function it_registers_any_provided_steps_in_the_wizard(): void
    {
        $this->artisan('make:wizard TestWizard --steps=Step1,Step2');

        $this->assertMatchesFileSnapshot(app_path('Wizards/TestWizard/TestWizard.php'));
    }
}
