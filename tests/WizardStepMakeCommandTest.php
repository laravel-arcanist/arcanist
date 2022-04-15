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

use Spatie\Snapshots\MatchesSnapshots;

class WizardStepMakeCommandTest extends TestCase
{
    use MatchesSnapshots;

    public function testItPrefillsTheStepsTitle(): void
    {
        $this->artisan('make:wizard-step Step1 TestWizard');

        $this->assertMatchesFileSnapshot(app_path('Wizards/TestWizard/Steps/Step1.php'));
    }

    public function testItPrefillsTheStepsSlug(): void
    {
        $this->artisan('make:wizard-step Step1 TestWizard');

        $this->assertMatchesFileSnapshot(app_path('Wizards/TestWizard/Steps/Step1.php'));
    }
}
