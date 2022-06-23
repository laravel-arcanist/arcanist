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
use Arcanist\Arcanist;
use Arcanist\Contracts\ResponseRenderer;
use Arcanist\Contracts\WizardActionResolver;
use Arcanist\Event\WizardFinished;
use Arcanist\Event\WizardFinishing;
use Arcanist\Event\WizardLoaded;
use Arcanist\Event\WizardSaving;
use Arcanist\Exception\UnknownStepException;
use Arcanist\Field;
use Arcanist\NullAction;
use Arcanist\Renderer\FakeResponseRenderer;
use Arcanist\Repository\FakeWizardRepository;
use Arcanist\StepResult;
use Arcanist\WizardStep;
use Generator;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Mockery as m;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WizardTest extends WizardTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
        $_SERVER['__onAfterComplete.called'] = 0;
        $_SERVER['__beforeDelete.called'] = 0;
        $_SERVER['__onAfterDelete.called'] = 0;

        Arcanist::boot([
            TestWizard::class,
            MultiStepWizard::class,
            SharedDataWizard::class,
            ErrorWizard::class,
        ]);
    }

    public function testItRendersTheFirstStepInAnWizard(): void
    {
        $renderer = new FakeResponseRenderer();
        $wizard = $this->createWizard(
            TestWizard::class,
            repository: $this->createWizardRepository(),
            renderer: $renderer,
        );

        $wizard->create(new Request());

        self::assertTrue($renderer->stepWasRendered(TestStep::class));
    }

    public function testItThrowsAnExceptionIfNoStepExistsForTheProvidedSlug(): void
    {
        $this->expectException(UnknownStepException::class);

        $wizard = $this->createWizard(TestWizard::class);

        $wizard->show(new Request(), '1', '::step-slug::');
    }

    public function testItGetsTheViewDataFromTheStep(): void
    {
        $renderer = new FakeResponseRenderer();
        $wizard = $this->createWizard(TestWizard::class, renderer: $renderer);

        $wizard->show(new Request(), '1', 'step-with-view-data');

        self::assertTrue($renderer->stepWasRendered(TestStepWithViewData::class, [
            'foo' => 'bar',
        ]));
    }

    public function testItRejectsAnInvalidFormRequest(): void
    {
        $this->expectException(ValidationException::class);

        $request = Request::create('::url::', 'POST', [
            'first_name' => '::first-name::',
        ]);
        $wizard = $this->createWizard(TestWizard::class);

        $wizard->store($request);
    }

    public function testItHandlesTheFormSubmitForTheFirstStepInTheWorkflow(): void
    {
        $request = Request::create('::url::', 'POST', [
            'first_name' => '::first-name::',
            'last_name' => '::last-name::',
        ]);
        $repo = new FakeWizardRepository();
        $wizard = $this->createWizard(TestWizard::class, repository: $repo);

        $wizard->store($request);

        self::assertEquals(
            [
                'first_name' => '::first-name::',
                'last_name' => '::last-name::',
            ],
            Arr::except($repo->loadData($wizard), '_arcanist'),
        );
    }

    public function testItRendersAStepForAnExistingWizardUsingTheSavedData(): void
    {
        $repo = new FakeWizardRepository([
            TestWizard::class => [
                1 => [
                    'first_name' => '::first-name::',
                    'last_name' => '::last-name::',
                ],
            ],
        ]);
        $renderer = new FakeResponseRenderer();
        $wizard = $this->createWizard(TestWizard::class, repository: $repo, renderer: $renderer);

        $wizard->show(new Request(), '1', 'step-name');

        self::assertTrue($renderer->stepWasRendered(TestStep::class, [
            'first_name' => '::first-name::',
            'last_name' => '::last-name::',
        ]));
    }

    public function testItHandlesTheFormSubmissionForAStepInAnExistingWizard(): void
    {
        $repo = $this->createWizardRepository([
            'first_name' => '::old-first-name::',
            'last_name' => '::old-last-name::',
        ]);
        $request = Request::create('::url::', 'PUT', [
            'first_name' => '::new-first-name::',
            'last_name' => '::old-last-name::',
        ]);
        $wizard = $this->createWizard(TestWizard::class, repository: $repo);

        $wizard->update($request, '1', 'step-name');

        self::assertEquals([
            'first_name' => '::new-first-name::',
            'last_name' => '::old-last-name::',
        ], Arr::except($repo->loadData($wizard), '_arcanist'));
    }

    public function testItRedirectsToTheNextStepAfterSubmittingANewWizard(): void
    {
        $renderer = new FakeResponseRenderer();
        $request = Request::create('::url::', 'PUT', [
            'first_name' => '::new-first-name::',
            'last_name' => '::old-last-name::',
        ]);
        $wizard = $this->createWizard(TestWizard::class, renderer: $renderer);

        $wizard->store($request);

        self::assertTrue($renderer->didRedirectTo(TestStepWithViewData::class));
    }

    public function testItRedirectsToTheNextStepAfterSubmittingAnExistingWizard(): void
    {
        $renderer = new FakeResponseRenderer();
        $request = Request::create('::url::', 'PUT', [
            'first_name' => '::new-first-name::',
            'last_name' => '::old-last-name::',
        ]);
        $wizard = $this->createWizard(TestWizard::class, renderer: $renderer);

        $wizard->update($request, '1', 'step-name');

        self::assertTrue($renderer->didRedirectTo(TestStepWithViewData::class));
    }

    public function testItReturnsTheWizardsTitle(): void
    {
        $wizard = $this->createWizard(TestWizard::class);

        $summary = $wizard->summary();

        self::assertEquals('::wizard-name::', $summary['title']);
    }

    /**
     * @dataProvider idProvider
     */
    public function testItReturnsTheWizardsIdInTheSummary(?int $id): void
    {
        $wizard = $this->createWizard(TestWizard::class);

        if (null !== $id) {
            $wizard->setId($id);
        }

        $summary = $wizard->summary();

        self::assertEquals($id, $summary['id']);
    }

    public function idProvider(): Generator
    {
        yield from [
            'no id' => [null],
            'with id' => [5],
        ];
    }

    public function testItReturnsTheWizardsSlugInTheSummary(): void
    {
        $wizard = $this->createWizard(TestWizard::class);

        $summary = $wizard->summary();

        self::assertEquals($wizard::$slug, $summary['slug']);
    }

    public function testItReturnsTheSlugOfEachStepInTheSummary(): void
    {
        $wizard = $this->createWizard(TestWizard::class);

        $summary = $wizard->summary();

        self::assertEquals('step-name', $summary['steps'][0]['slug']);
        self::assertEquals('step-with-view-data', $summary['steps'][1]['slug']);
    }

    public function testItRendersInformationAboutTheCompletionOfEachStep(): void
    {
        $wizard = $this->createWizard(TestWizard::class);

        $summary = $wizard->summary();

        self::assertTrue($summary['steps'][0]['isComplete']);
        self::assertFalse($summary['steps'][1]['isComplete']);
    }

    public function testItRendersTheTitleOfEachStepInTheSummary(): void
    {
        $wizard = $this->createWizard(TestWizard::class);

        $summary = $wizard->summary();

        self::assertEquals('::step-1-name::', $summary['steps'][0]['title']);
        self::assertEquals('::step-2-name::', $summary['steps'][1]['title']);
    }

    public function testItMarksTheFirstStepAsActiveOnTheCreateRoute(): void
    {
        $wizard = $this->createWizard(TestWizard::class);
        $wizard->create(new Request());

        $summary = $wizard->summary();

        self::assertTrue($summary['steps'][0]['active']);
        self::assertFalse($summary['steps'][1]['active']);
    }

    public function testItMarksTheCurrentStepActiveForTheShowRoute(): void
    {
        $wizard = $this->createWizard(TestWizard::class);
        $wizard->show(new Request(), '1', 'step-with-view-data');

        $summary = $wizard->summary();

        self::assertFalse($summary['steps'][0]['active']);
        self::assertTrue($summary['steps'][1]['active']);
    }

    /**
     * @dataProvider wizardExistsProvider
     */
    public function testItCanCheckIfAnExistingWizardIsBeingEdited(?int $id, bool $expected): void
    {
        $wizard = $this->createWizard(TestWizard::class);

        if (null !== $id) {
            $wizard->setId($id);
        }

        self::assertEquals($expected, $wizard->exists());
    }

    public function wizardExistsProvider(): Generator
    {
        yield from [
            'does not exist' => [null, false],
            'exists' => [1, true],
        ];
    }

    public function testItIncludesTheLinkToTheStepInTheSummary(): void
    {
        $wizard = $this->createWizard(TestWizard::class);
        $wizard->setId(1);

        $summary = $wizard->summary();

        self::assertEquals(
            route('wizard.' . $wizard::$slug . '.show', [1, 'step-name']),
            $summary['steps'][0]['url'],
        );
        self::assertEquals(
            route('wizard.' . $wizard::$slug . '.show', [1, 'step-with-view-data']),
            $summary['steps'][1]['url'],
        );
    }

    public function testItDoesNotIncludeTheStepUrlsIfTheWizardDoesNotExist(): void
    {
        $wizard = $this->createWizard(TestWizard::class);

        $summary = $wizard->summary();

        self::assertNull($summary['steps'][0]['url']);
        self::assertNull($summary['steps'][1]['url']);
    }
    
    public function testItDoesNotIncludeOmittedStepsInTheSummary(): void
    {
        $wizard = $this->createWizard(OmittedStepWizard::class);

        $summary = $wizard->summary();

        self::assertCount(1, $summary['steps']);
        self::assertSame('step-name', $summary['steps'][0]['slug']);
    }

    /**
     * @dataProvider sharedDataProvider
     */
    public function testItIncludesSharedDataInTheViewResponse(callable $callWizard): void
    {
        $renderer = new FakeResponseRenderer();
        $wizard = $this->createWizard(
            SharedDataWizard::class,
            repository: $this->createWizardRepository(wizardClass: SharedDataWizard::class),
            renderer: $renderer,
        );

        $callWizard($wizard);

        self::assertTrue($renderer->stepWasRendered(TestStep::class, [
            'first_name' => '',
            'last_name' => '',
            'shared_1' => '::shared-1::',
            'shared_2' => '::shared-2::',
        ]));
    }

    public function sharedDataProvider(): Generator
    {
        yield from [
            'create' => [
                function (AbstractWizard $wizard): void {
                    $wizard->create(new Request());
                },
            ],

            'show' => [
                function (AbstractWizard $wizard): void {
                    $wizard->show(new Request(), '1', 'step-name');
                },
            ],
        ];
    }

    public function beforeSaveProvider(): Generator
    {
        $validRequest = Request::create('::uri::', 'POST', [
            'first_name' => '::first-name::',
            'last_name' => '::last-name::',
        ]);

        yield from [
            'store' => [
                fn (AbstractWizard $wizard) => $wizard->store($validRequest),
            ],
            'update' => [
                fn (AbstractWizard $wizard) => $wizard->update($validRequest, '1', 'step-name'),
            ],
        ];
    }

    public function testItFiresAnEventAfterTheLastStepOfTheWizardWasFinished(): void
    {
        $wizard = $this->createWizard(TestWizard::class);

        $wizard->update(new Request(), '1', 'step-with-view-data');

        Event::assertDispatched(
            WizardFinishing::class,
            fn (WizardFinishing $event) => $event->wizard === $wizard,
        );
    }

    public function testItCallsTheOnAfterCompleteActionAfterTheLastStepWasSubmitted(): void
    {
        $actionSpy = m::spy(WizardAction::class);
        $actionSpy->allows('execute')->andReturns(ActionResult::success());
        $actionResolver = m::mock(WizardActionResolver::class);
        $actionResolver
            ->allows('resolveAction')
            ->with(NullAction::class)
            ->andReturn($actionSpy);
        $wizard = $this->createWizard(TestWizard::class, resolver: $actionResolver);

        $wizard->update(new Request(), '1', 'step-with-view-data');

        $actionSpy->shouldHaveReceived('execute')
            ->once();
    }

    public function testItPassesAllGatheredDataToTheActionByDefault(): void
    {
        $actionSpy = new class() extends WizardAction {
            public array $payload = [];

            public function execute($payload): ActionResult
            {
                $this->payload = $payload;

                return $this->success();
            }
        };
        $actionResolver = m::mock(WizardActionResolver::class);
        $actionResolver
            ->allows('resolveAction')
            ->andReturn($actionSpy);
        $wizard = $this->createWizard(SharedDataWizard::class, resolver: $actionResolver);

        $wizard->update(Request::create('::url::', 'POST', [
            'first_name' => '::first-name::',
            'last_name' => '::last-name::',
        ]), '1', 'step-name');

        self::assertEquals(
            ['first_name' => '::first-name::', 'last_name' => '::last-name::'],
            $actionSpy->payload,
        );
    }

    public function testItFiresAnEventAfterTheOnCompleteCallbackWasRan(): void
    {
        $wizard = $this->createWizard(TestWizard::class);

        $wizard->update(new Request(), '1', 'step-with-view-data');

        Event::assertDispatched(
            WizardFinished::class,
            fn (WizardFinished $event) => $event->wizard === $wizard,
        );
    }

    public function testItCallsTheOnAfterCompleteHookOfTheWizard(): void
    {
        $wizard = $this->createWizard(TestWizard::class);

        $wizard->update(new Request(), '1', 'step-with-view-data');

        self::assertEquals(1, $_SERVER['__onAfterComplete.called']);
    }

    /**
     * @dataProvider beforeSaveProvider
     */
    public function testItFiresAnEventBeforeTheWizardGetsSaved(callable $callwizard): void
    {
        $wizard = $this->createWizard(TestWizard::class);

        $callwizard($wizard);

        Event::assertDispatched(
            WizardSaving::class,
            fn (WizardSaving $e) => $e->wizard === $wizard,
        );
    }

    /**
     * @dataProvider afterSaveProvider
     */
    public function testItFiresAnEventAfterAnWizardWasLoaded(callable $callwizard): void
    {
        $wizard = $this->createWizard(TestWizard::class);

        $callwizard($wizard);

        Event::assertDispatched(
            WizardLoaded::class,
            fn (WizardLoaded $e) => $e->wizard === $wizard,
        );
    }

    public function afterSaveProvider(): Generator
    {
        yield from [
            'update' => [
                function (AbstractWizard $wizard): void {
                    $wizard->update(new Request(), '1', 'step-with-view-data');
                },
            ],

            'show' => [
                function (AbstractWizard $wizard): void {
                    $wizard->show(new Request(), '1', 'step-with-view-data');
                },
            ],
        ];
    }

    public function testItCanBeDeleted(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $wizard = $this->createWizard(TestWizard::class);

        $wizard->destroy(new Request(), '1');

        $wizard->show(new Request(), '1', 'step-name');
    }

    public function testItRedirectsToTheDefaultRouteAfterTheWizardHasBeenDeleted(): void
    {
        config(['arcanist.redirect_url' => '::redirect-url::']);

        $wizard = $this->createWizard(TestWizard::class);

        $response = new TestResponse($wizard->destroy(new Request(), '1'));

        $response->assertRedirect('::redirect-url::');
    }

    public function testItRedirectsToTheCorrectUrlIfTheDefaultUrlWasOverwritten(): void
    {
        $wizard = $this->createWizard(SharedDataWizard::class);

        $response = new TestResponse($wizard->destroy(new Request(), '1'));

        $response->assertRedirect('::other-route::');
    }

    public function testItCallsTheOnAfterDeleteHookOfTheWizard(): void
    {
        $wizard = $this->createWizard(TestWizard::class);

        $wizard->destroy(new Request(), '1');

        self::assertEquals(1, $_SERVER['__onAfterDelete.called']);
    }

    /**
     * @dataProvider resumeWizardProvider
     */
    public function testItRedirectsToTheNextUncompletedStepIfNoStepSlugWasGiven(callable $createwizard, string $expectedStep): void
    {
        $renderer = new FakeResponseRenderer();
        $wizard = $createwizard($renderer);

        $wizard->show(new Request(), '1');

        self::assertTrue($renderer->didRedirectTo($expectedStep));
    }

    public function resumeWizardProvider(): Generator
    {
        yield from [
            [
                function (ResponseRenderer $renderer) {
                    return $this->createWizard(TestWizard::class, renderer: $renderer);
                },
                TestStepWithViewData::class,
            ],
            [
                function (ResponseRenderer $renderer) {
                    return $this->createWizard(MultiStepWizard::class, renderer: $renderer);
                },
                TestStepWithViewData::class,
            ],
        ];
    }

    /**
     * @dataProvider errorWizardProvider
     */
    public function testItRedirectsToTheSameStepWithAnErrorIfTheStepWasNotCompletedSuccessfully(callable $callWizard): void
    {
        $renderer = new FakeResponseRenderer();
        $wizard = $this->createWizard(ErrorWizard::class, renderer: $renderer);

        $callWizard($wizard);

        self::assertTrue(
            $renderer->didRedirectWithError(ErrorStep::class, '::error-message::'),
        );
    }

    public function testItRedirectsBackToLastStepWithAnErrorIfTheActionWasNotSuccessful(): void
    {
        $renderer = new FakeResponseRenderer();
        $resolver = m::mock(WizardActionResolver::class);
        $resolver->allows('resolveAction')
            ->andReturns(new class() extends WizardAction {
                public function execute(mixed $payload): ActionResult
                {
                    return $this->failure('::message::');
                }
            });
        $wizard = $this->createWizard(TestWizard::class, renderer: $renderer, resolver: $resolver);

        $wizard->update(new Request(), '1', 'step-with-view-data');

        self::assertTrue(
            $renderer->didRedirectWithError(TestStepWithViewData::class, '::message::'),
        );
    }

    public function errorWizardProvider()
    {
        yield from [
            'store' => [
                function (AbstractWizard $wizard): void {
                    $wizard->store(new Request());
                },
            ],

            'update' => [
                function (AbstractWizard $wizard): void {
                    $wizard->update(new Request(), '1', '::error-step::');
                },
            ],
        ];
    }

    public function testItMarksAStepAsCompletedIfItWasSubmittedSuccessfullyOnce(): void
    {
        $repo = $this->createWizardRepository();
        $wizard = $this->createWizard(TestWizard::class, repository: $repo);
        $request = Request::create('::uri::', 'POST', [
            'first_name' => '::first-name::',
            'last_name' => '::first-name::',
        ]);

        $wizard->update($request, '1', 'step-name');

        self::assertTrue($repo->loadData($wizard)['_arcanist']['step-name']);
    }

    public function testItDoesNotMarkAStepAsCompleteIfItFailed(): void
    {
        $repo = $this->createWizardRepository(wizardClass: ErrorWizard::class);
        $wizard = $this->createWizard(ErrorWizard::class, repository: $repo);

        $wizard->update(new Request(), '1', '::error-step::');

        self::assertNull(
            $repo->loadData($wizard)['_arcanist']['::error-step::'] ?? null,
        );
    }

    public function testItMergesInformationWithInformationAboutAlreadyCompletedSteps(): void
    {
        $repo = $this->createWizardRepository([
            '_arcanist' => [
                'regular-step' => true,
            ],
        ]);
        $wizard = $this->createWizard(TestWizard::class, repository: $repo);
        $wizard->setId(1);

        $wizard->update(new Request(), '1', 'step-with-view-data');

        self::assertEquals([
            'regular-step' => true,
            'step-with-view-data' => true,
        ], $repo->loadData($wizard)['_arcanist']);
    }
}

class TestWizard extends AbstractWizard
{
    public static string $slug = 'wizard-name';
    public static string $title = '::wizard-name::';
    protected array $steps = [
        TestStep::class,
        TestStepWithViewData::class,
    ];

    protected function onAfterComplete(ActionResult $result): Response|Responsable|Renderable
    {
        ++$_SERVER['__onAfterComplete.called'];

        return redirect()->back();
    }

    protected function onAfterDelete(): Response|Responsable|Renderable
    {
        ++$_SERVER['__onAfterDelete.called'];

        return parent::onAfterDelete();
    }

    protected function beforeDelete(Request $request): void
    {
        ++$_SERVER['__beforeDelete.called'];
    }

    protected function cancelText(): string
    {
        return '::cancel-text::';
    }
}

class MultiStepWizard extends AbstractWizard
{
    protected array $steps = [
        TestStep::class,
        DummyStep::class,
        TestStepWithViewData::class,
    ];
}

class OmittedStepWizard extends AbstractWizard
{
    protected array $steps = [
        TestStep::class,
        OmittedStep::class,
    ];
}

class SharedDataWizard extends AbstractWizard
{
    protected array $steps = [
        TestStep::class,
    ];

    public function sharedData(Request $request): array
    {
        return [
            'shared_1' => '::shared-1::',
            'shared_2' => '::shared-2::',
        ];
    }

    protected function onAfterComplete(ActionResult $result): RedirectResponse
    {
        return redirect()->back();
    }

    protected function redirectTo(): string
    {
        return '::other-route::';
    }
}

class ErrorWizard extends AbstractWizard
{
    protected array $steps = [
        ErrorStep::class,
    ];
}

class TestStep extends WizardStep
{
    public string $title = '::step-1-name::';
    public string $slug = 'step-name';

    public function fields(): array
    {
        return [
            Field::make('first_name')
                ->rules(['required']),

            Field::make('last_name')
                ->rules(['required']),
        ];
    }

    public function viewData(Request $request): array
    {
        return [
            'first_name' => $this->data('first_name'),
            'last_name' => $this->data('last_name'),
        ];
    }

    public function isComplete(): bool
    {
        return true;
    }

    public function beforeSaving(Request $request, array $data): void
    {
        ++$_SERVER['__beforeSaving.called'];
    }
}

class TestStepWithViewData extends WizardStep
{
    public string $title = '::step-2-name::';
    public string $slug = 'step-with-view-data';

    public function viewData(Request $request): array
    {
        return ['foo' => 'bar'];
    }

    public function beforeSaving(Request $request, array $data): void
    {
        $this->setData('::key::', '::value::');
    }
}

class OmittedStep extends WizardStep
{
    public string $slug = 'omitted-step-name';

    public function omit(): bool
    {
        return true;
    }
}

class DummyStep extends WizardStep
{
    public function isComplete(): bool
    {
        return true;
    }
}

class ErrorStep extends WizardStep
{
    public string $slug = '::error-step::';

    protected function handle(Request $request, array $payload): StepResult
    {
        return $this->error('::error-message::');
    }
}
