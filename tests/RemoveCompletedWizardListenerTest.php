<?php declare(strict_types=1);

namespace Arcanist\Tests;

use Mockery as m;
use Arcanist\AbstractWizard;
use PHPUnit\Framework\TestCase;
use Arcanist\Event\WizardFinished;
use Arcanist\Repository\FakeWizardRepository;
use Arcanist\Listener\RemoveCompletedWizardListener;

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
