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
use Arcanist\Action\ActionResult;
use Arcanist\Action\WizardAction;
use Arcanist\Contracts\ResponseRenderer;
use Arcanist\Contracts\WizardActionResolver;
use Arcanist\Contracts\WizardRepository;
use Arcanist\Renderer\FakeResponseRenderer;
use Arcanist\Repository\FakeWizardRepository;
use Illuminate\Support\Facades\Event;
use Mockery as m;

class WizardTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
    }

    /**
     * @param class-string<AbstractWizard> $wizardClass
     */
    protected function createWizard(
        string $wizardClass,
        ?WizardRepository $repository = null,
        ?ResponseRenderer $renderer = null,
        ?WizardActionResolver $resolver = null,
    ): AbstractWizard {
        $repository ??= $this->createWizardRepository(wizardClass: $wizardClass);
        $renderer ??= new FakeResponseRenderer();
        $resolver ??= new class() implements WizardActionResolver {
            public function resolveAction(string $actionClass): WizardAction
            {
                $action = m::mock(WizardAction::class);
                $action->allows('execute')->andReturn(ActionResult::success());

                return $action;
            }
        };

        return new $wizardClass($repository, $renderer, $resolver);
    }

    /**
     * @param null|class-string<AbstractWizard> $wizardClass
     * @param array<string, mixed>              $data
     */
    protected function createWizardRepository(array $data = [], ?string $wizardClass = null): FakeWizardRepository
    {
        return new FakeWizardRepository([
            $wizardClass ?: TestWizard::class => [
                1 => $data,
            ],
        ]);
    }
}
