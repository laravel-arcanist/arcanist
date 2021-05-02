<?php declare(strict_types=1);

namespace Arcanist\Tests;

use Mockery as m;
use Arcanist\AbstractWizard;
use Arcanist\Action\ActionResult;
use Arcanist\Action\WizardAction;
use Illuminate\Support\Facades\Event;
use Arcanist\Contracts\ResponseRenderer;
use Arcanist\Contracts\WizardRepository;
use Arcanist\Renderer\FakeResponseRenderer;
use Arcanist\Contracts\WizardActionResolver;
use Arcanist\Repository\FakeWizardRepository;

class WizardTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
    }

    protected function createWizard(
        string $wizardClass,
        ?WizardRepository $repository = null,
        ?ResponseRenderer $renderer = null,
        ?WizardActionResolver $resolver = null
    ): AbstractWizard {
        $repository ??= $this->createWizardRepository(wizardClass: $wizardClass);
        $renderer ??= new FakeResponseRenderer();
        $resolver ??= new class implements WizardActionResolver {
            public function resolveAction(string $actionClass): WizardAction
            {
                $action = m::mock(WizardAction::class);
                $action->allows('execute')->andReturn(ActionResult::success());

                return $action;
            }
        };

        return new $wizardClass($repository, $renderer, $resolver);
    }

    protected function createWizardRepository(array $data = [], ?string $wizardClass = null)
    {
        return new FakeWizardRepository([
            $wizardClass ?: TestWizard::class => [
                '1' => $data
            ],
        ]);
    }
}
