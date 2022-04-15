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
use Arcanist\Commands\CleanupExpiredWizards;
use Arcanist\Repository\Wizard;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Mockery as m;

class CleanupExpiredWizardsTest extends TestCase
{
    use RefreshDatabase;
    private CleanupExpiredWizards $job;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(now());
    }

    public function testItOnlyDeletesAWizardOnceItHasExpired(): void
    {
        $wizardClass = m::spy(AbstractWizard::class);
        $wizard = Wizard::create([
            'data' => '[]',
            'class' => $wizardClass::class,
            'updated_at' => now(),
        ]);

        Artisan::call('arcanist:clean-expired');
        $this->assertDatabaseHas('wizards', ['id' => $wizard->id]);

        $this->travel(1)->days();

        Artisan::call('arcanist:clean-expired');
        $this->assertDatabaseMissing('wizards', ['id' => $wizard->id]);
    }
}
