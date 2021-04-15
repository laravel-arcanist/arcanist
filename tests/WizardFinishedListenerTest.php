<?php declare(strict_types=1);

namespace Tests;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Sassnowski\Arcanist\AbstractWizard;
use function PHPUnit\Framework\assertEquals;
use Sassnowski\Arcanist\Event\WizardFinishing;
use Sassnowski\Arcanist\Listener\CallOnWizardCompleteAction;

class WizardFinishedListenerTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER['__action.called'] = 0;
        $_SERVER['__action.param'] = null;
    }

    /** @test */
    public function it_calls_the_configured_action(): void
    {
        $listener = new CallOnWizardCompleteAction();
        $wizard = m::mock(AbstractWizard::class);
        $wizard->makePartial();
        $wizard->onCompleteAction = TestAction::class;

        $listener->handle(new WizardFinishing($wizard));

        assertEquals(1, $_SERVER['__action.called']);
    }

    /** @test */
    public function it_passes_the_transformed_wizard_data_to_the_action(): void
    {
        $listener = new CallOnWizardCompleteAction();
        $wizard = m::mock(AbstractWizard::class);
        $wizard->onCompleteAction = TestAction::class;
        $wizard->allows('transformwizardData')->andReturn('::foo::');
        $wizard->makePartial();

        $listener->handle(new WizardFinishing($wizard));

        assertEquals('::foo::', $_SERVER['__action.param']);
    }

    /** @test */
    public function it_sets_any_data_returned_by_the_action_on_the_wizard(): void
    {
        $listener = new CallOnWizardCompleteAction();
        $wizard = m::mock(AbstractWizard::class)->makePartial();
        $wizard->onCompleteAction = TestAction::class;

        $listener->handle(new WizardFinishing($wizard));

        assertEquals('::after-complete-data::', $wizard->data('completedPayload'));
    }
}

class TestAction
{
    public function execute($args = null)
    {
        $_SERVER['__action.called']++;
        $_SERVER['__action.param'] = $args;

        return '::after-complete-data::';
    }
}
