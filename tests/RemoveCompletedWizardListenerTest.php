<?php declare(strict_types=1);

namespace Tests;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Sassnowski\Arcanist\AbstractWizard;
use Sassnowski\Arcanist\Event\WizardFinished;
use Sassnowski\Arcanist\Repository\FakeWizardRepository;
use Sassnowski\Arcanist\Listener\RemoveCompletedWizardListener;

class RemoveCompletedWizardListenerTest extends TestCase
{
    /** @test */
    public function it_removes_a_wizard_after_it_was_completed(): void
    {
        $wizard = m::mock(AbstractWizard::class)->makePartial();
        $wizard->setId(1);
        $repository = new FakeWizardRepository();
        $event = new WizardFinished($wizard);
        $listener = new RemoveCompletedWizardListener($repository);

        $listener->handle($event);

        self::assertFalse($wizard->exists());
    }
}
