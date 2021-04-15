<?php declare(strict_types=1);

namespace Tests\Assistant;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertEquals;
use Sassnowski\Arcanist\Assistant\AbstractAssistant;
use Sassnowski\Arcanist\Assistant\Event\AssistantFinishing;
use Sassnowski\Arcanist\Assistant\Listener\CallOnAssistantCompleteAction;

class AssistantFinishedListenerTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER['__action.called'] = 0;
        $_SERVER['__action.param'] = null;
    }

    /** @test */
    public function it_calls_the_configured_action(): void
    {
        $listener = new CallOnAssistantCompleteAction();
        $assistant = m::mock(AbstractAssistant::class);
        $assistant->makePartial();
        $assistant->onCompleteAction = TestAction::class;

        $listener->handle(new AssistantFinishing($assistant));

        assertEquals(1, $_SERVER['__action.called']);
    }

    /** @test */
    public function it_passes_the_transformed_assistant_data_to_the_action(): void
    {
        $listener = new CallOnAssistantCompleteAction();
        $assistant = m::mock(AbstractAssistant::class);
        $assistant->onCompleteAction = TestAction::class;
        $assistant->allows('transformAssistantData')->andReturn('::foo::');
        $assistant->makePartial();

        $listener->handle(new AssistantFinishing($assistant));

        assertEquals('::foo::', $_SERVER['__action.param']);
    }

    /** @test */
    public function it_sets_any_data_returned_by_the_action_on_the_assistant(): void
    {
        $listener = new CallOnAssistantCompleteAction();
        $assistant = m::mock(AbstractAssistant::class)->makePartial();
        $assistant->onCompleteAction = TestAction::class;

        $listener->handle(new AssistantFinishing($assistant));

        assertEquals('::after-complete-data::', $assistant->data('completedPayload'));
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
