<?php declare(strict_types=1);

namespace Tests;

use Mockery as m;
use Carbon\Carbon;
use Arcanist\AbstractWizard;
use Arcanist\Repository\Wizard;
use Illuminate\Support\Facades\Artisan;
use Arcanist\Commands\CleanupExpiredWizards;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CleanupExpiredWizardsTest extends TestCase
{
    use RefreshDatabase;

    private CleanupExpiredWizards $job;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(now());
    }

    /** @test */
    public function it_only_deletes_a_wizard_once_it_has_expired(): void
    {
        $wizardClass = m::spy(AbstractWizard::class);
        $wizard = Wizard::create([
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
