<?php declare(strict_types=1);

namespace Tests;

use Mockery as m;
use Illuminate\Support\Facades\Event;
use Sassnowski\Arcanist\WizardAction;
use Sassnowski\Arcanist\AbstractWizard;
use Sassnowski\Arcanist\Action\ActionResult;
use Sassnowski\Arcanist\Contracts\ResponseRenderer;
use Sassnowski\Arcanist\Contracts\WizardRepository;
use Sassnowski\Arcanist\Renderer\FakeResponseRenderer;
use Sassnowski\Arcanist\Contracts\WizardActionResolver;
use Sassnowski\Arcanist\Repository\FakeWizardRepository;

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
                1 => $data
            ],
        ]);
    }
}
