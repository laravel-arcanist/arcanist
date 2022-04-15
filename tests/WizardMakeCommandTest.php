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

use Generator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\Snapshots\MatchesSnapshots;
use function app_path;

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
     * @dataProvider stepCommandInvocationProvider
     */
    public function testItCreatesStepsIfTheStepOptionWasProvided(array $steps): void
    {
        $option = \implode(',', $steps);

        $this
            ->withoutExceptionHandling()
            ->artisan('make:wizard ' . $this->wizardName . ' --steps=' . $option);

        foreach ($steps as $step) {
            self::assertTrue(File::exists(
                app_path('Wizards/' . $this->wizardName . '/Steps/' . $step . '.php'),
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

    public function testItDoesNotCreateStepsIfTheOptionIsntProvided(): void
    {
        $this->artisan('make:wizard ' . $this->wizardName);

        self::assertFalse(File::exists(app_path('Wizards/' . $this->wizardName . '/Steps')));
    }

    /**
     * @dataProvider emptyStepNameProvider
     */
    public function testItDoesNotCreateStepsIfNoStepNamesAreProvided(string $option): void
    {
        $this->artisan('make:wizard ' . $this->wizardName . ' ' . $option);

        self::assertFalse(File::exists(app_path('Wizards/' . $this->wizardName . '/Steps')));
    }

    public function emptyStepNameProvider(): Generator
    {
        yield from [
            ['--steps'],
            ['--steps='],
        ];
    }

    public function testItPrefillsTheGeneratedWizardsTitleProperty(): void
    {
        $this->artisan('make:wizard TestWizard');

        $this->assertMatchesFileSnapshot(app_path('Wizards/TestWizard/TestWizard.php'));
    }

    public function testItPrefillsTheGeneratedWizardsSlugProperty(): void
    {
        $this->artisan('make:wizard TestWizard');

        $this->assertMatchesFileSnapshot(app_path('Wizards/TestWizard/TestWizard.php'));
    }

    public function testItRegistersAnyProvidedStepsInTheWizard(): void
    {
        $this->artisan('make:wizard TestWizard --steps=Step1,Step2');

        $this->assertMatchesFileSnapshot(app_path('Wizards/TestWizard/TestWizard.php'));
    }
}
