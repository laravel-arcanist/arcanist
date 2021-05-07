<?php declare(strict_types=1);

namespace Arcanist\Tests;

use Generator;
use Mockery as m;
use Arcanist\Field;
use Arcanist\Arcanist;
use Arcanist\NullAction;
use Arcanist\StepResult;
use Arcanist\WizardStep;
use Illuminate\Support\Arr;
use Arcanist\AbstractWizard;
use Illuminate\Http\Request;
use Arcanist\Event\WizardLoaded;
use Arcanist\Event\WizardSaving;
use Arcanist\Action\ActionResult;
use Arcanist\Action\WizardAction;
use Arcanist\Event\WizardFinished;
use Arcanist\Event\WizardFinishing;
use Illuminate\Testing\TestResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Event;
use Arcanist\Contracts\ResponseRenderer;
use Arcanist\Renderer\FakeResponseRenderer;
use Arcanist\Contracts\WizardActionResolver;
use Arcanist\Exception\UnknownStepException;
use Arcanist\Repository\FakeWizardRepository;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WizardTest extends WizardTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
        $_SERVER['__onAfterComplete.called'] = 0;
        $_SERVER['__beforeDelete.called'] = 0;

        Arcanist::boot([
            TestWizard::class,
            MultiStepWizard::class,
            SharedDataWizard::class,
            ErrorWizard::class,
        ]);
    }

    /** @test */
    public function it_renders_the_first_step_in_an_wizard(): void
    {
        $renderer = new FakeResponseRenderer();
        $wizard = $this->createWizard(
            TestWizard::class,
            repository: $this->createWizardRepository(),
            renderer: $renderer
        );

        $wizard->create(new Request());

        self::assertTrue($renderer->stepWasRendered(TestStep::class));
    }

    /** @test */
    public function it_throws_an_exception_if_no_step_exists_for_the_provided_slug(): void
    {
        $this->expectException(UnknownStepException::class);

        $wizard = $this->createWizard(TestWizard::class);

        $wizard->show(new Request(), '1', '::step-slug::');
    }

    /** @test */
    public function it_gets_the_view_data_from_the_step(): void
    {
        $renderer = new FakeResponseRenderer();
        $wizard = $this->createWizard(TestWizard::class, renderer: $renderer);

        $wizard->show(new Request(), '1', 'step-with-view-data');

        self::assertTrue($renderer->stepWasRendered(TestStepWithViewData::class, [
            'foo' => 'bar'
        ]));
    }

    /** @test */
    public function it_rejects_an_invalid_form_request(): void
    {
        $this->expectException(ValidationException::class);

        $request = Request::create('::url::', 'POST', [
            'first_name' => '::first-name::',
        ]);
        $wizard = $this->createWizard(TestWizard::class);

        $wizard->store($request);
    }

    /** @test */
    public function it_handles_the_form_submit_for_the_first_step_in_the_workflow(): void
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
            Arr::except($repo->loadData($wizard), '_arcanist')
        );
    }

    /** @test */
    public function it_renders_a_step_for_an_existing_wizard_using_the_saved_data(): void
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

    /** @test */
    public function it_handles_the_form_submission_for_a_step_in_an_existing_wizard(): void
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

    /** @test */
    public function it_redirects_to_the_next_step_after_submitting_a_new_wizard(): void
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

    /** @test */
    public function it_redirects_to_the_next_step_after_submitting_an_existing_wizard(): void
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

    /** @test */
    public function it_returns_the_wizards_title(): void
    {
        $wizard = $this->createWizard(TestWizard::class);

        $summary = $wizard->summary();

        self::assertEquals('::wizard-name::', $summary['title']);
    }

    /**
     * @test
     * @dataProvider idProvider
     */
    public function it_returns_the_wizards_id_in_the_summary(?int $id): void
    {
        $wizard = $this->createWizard(TestWizard::class);

        if ($id !== null) {
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

    /** @test */
    public function it_returns_the_wizards_slug_in_the_summary(): void
    {
        $wizard = $this->createWizard(TestWizard::class);

        $summary = $wizard->summary();

        self::assertEquals($wizard::$slug, $summary['slug']);
    }

    /** @test */
    public function it_returns_the_slug_of_each_step_in_the_summary(): void
    {
        $wizard = $this->createWizard(TestWizard::class);

        $summary = $wizard->summary();

        self::assertEquals('step-name', $summary['steps'][0]['slug']);
        self::assertEquals('step-with-view-data', $summary['steps'][1]['slug']);
    }

    /** @test */
    public function it_renders_information_about_the_completion_of_each_step(): void
    {
        $wizard = $this->createWizard(TestWizard::class);

        $summary = $wizard->summary();

        self::assertTrue($summary['steps'][0]['isComplete']);
        self::assertFalse($summary['steps'][1]['isComplete']);
    }

    /** @test */
    public function it_renders_the_title_of_each_step_in_the_summary(): void
    {
        $wizard = $this->createWizard(TestWizard::class);

        $summary = $wizard->summary();

        self::assertEquals('::step-1-name::', $summary['steps'][0]['title']);
        self::assertEquals('::step-2-name::', $summary['steps'][1]['title']);
    }

    /** @test */
    public function it_marks_the_first_step_as_active_on_the_create_route(): void
    {
        $wizard = $this->createWizard(TestWizard::class);
        $wizard->create(new Request());

        $summary = $wizard->summary();

        self::assertTrue($summary['steps'][0]['active']);
        self::assertFalse($summary['steps'][1]['active']);
    }

    /** @test */
    public function it_marks_the_current_step_active_for_the_show_route(): void
    {
        $wizard = $this->createWizard(TestWizard::class);
        $wizard->show(new Request(), '1', 'step-with-view-data');

        $summary = $wizard->summary();

        self::assertFalse($summary['steps'][0]['active']);
        self::assertTrue($summary['steps'][1]['active']);
    }

    /**
     * @test
     * @dataProvider wizardExistsProvider
     */
    public function it_can_check_if_an_existing_wizard_is_being_edited(?int $id, bool $expected): void
    {
        $wizard = $this->createWizard(TestWizard::class);

        if ($id !== null) {
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

    /** @test */
    public function it_includes_the_link_to_the_step_in_the_summary(): void
    {
        $wizard = $this->createWizard(TestWizard::class);
        $wizard->setId(1);

        $summary = $wizard->summary();

        self::assertEquals(
            route('wizard.' . $wizard::$slug . '.show', [1, 'step-name']),
            $summary['steps'][0]['url']
        );
        self::assertEquals(
            route('wizard.' . $wizard::$slug . '.show', [1, 'step-with-view-data']),
            $summary['steps'][1]['url']
        );
    }

    /** @test */
    public function it_does_not_include_the_step_urls_if_the_wizard_does_not_exist(): void
    {
        $wizard = $this->createWizard(TestWizard::class);

        $summary = $wizard->summary();

        self::assertNull($summary['steps'][0]['url']);
        self::assertNull($summary['steps'][1]['url']);
    }

    /**
     * @test
     * @dataProvider sharedDataProvider
     */
    public function it_includes_shared_data_in_the_view_response(callable $callWizard): void
    {
        $renderer = new FakeResponseRenderer();
        $wizard = $this->createWizard(
            SharedDataWizard::class,
            repository: $this->createWizardRepository(wizardClass:  SharedDataWizard::class),
            renderer: $renderer
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
                function (AbstractWizard $wizard) {
                    $wizard->create(new Request());
                }
            ],

            'show' => [
                function (AbstractWizard $wizard) {
                    $wizard->show(new Request(), '1', 'step-name');
                }
            ]
        ];
    }

    public function beforeSaveProvider(): Generator
    {
        $validRequest = Request::create('::uri::', 'POST', [
            'first_name' => '::first-name::',
            'last_name' => '::last-name::',
        ]);

        yield from  [
            'store' => [
                fn (AbstractWizard $wizard) => $wizard->store($validRequest)
            ],
            'update' => [
                fn (AbstractWizard $wizard) => $wizard->update($validRequest, '1', 'step-name')
            ]
        ];
    }

    /** @test */
    public function it_fires_an_event_after_the_last_step_of_the_wizard_was_finished(): void
    {
        $wizard = $this->createWizard(TestWizard::class);

        $wizard->update(new Request(), '1', 'step-with-view-data');

        Event::assertDispatched(
            WizardFinishing::class,
            fn (WizardFinishing $event) => $event->wizard === $wizard
        );
    }

    /** @test */
    public function it_calls_the_on_after_complete_action_after_the_last_step_was_submitted(): void
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

    /** @test */
    public function it_passes_all_gathered_data_to_the_action_by_default(): void
    {
        $actionSpy = new class extends WizardAction {
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
            $actionSpy->payload
        );
    }

    /** @test */
    public function it_fires_an_event_after_the_onComplete_callback_was_ran(): void
    {
        $wizard = $this->createWizard(TestWizard::class);

        $wizard->update(new Request(), '1', 'step-with-view-data');

        Event::assertDispatched(
            WizardFinished::class,
            fn (WizardFinished $event) => $event->wizard === $wizard
        );
    }

    /** @test */
    public function it_calls_the_on_after_complete_hook_of_the_wizard(): void
    {
        $wizard = $this->createWizard(TestWizard::class);

        $wizard->update(new Request(), '1', 'step-with-view-data');

        self::assertEquals(1, $_SERVER['__onAfterComplete.called']);
    }

    /**
     * @test
     * @dataProvider beforeSaveProvider
     */
    public function it_fires_an_event_before_the_wizard_gets_saved(callable $callwizard): void
    {
        $wizard = $this->createWizard(TestWizard::class);

        $callwizard($wizard);

        Event::assertDispatched(
            WizardSaving::class,
            fn (WizardSaving $e) => $e->wizard === $wizard
        );
    }

    /**
     * @test
     * @dataProvider afterSaveProvider
     */
    public function it_fires_an_event_after_an_wizard_was_loaded(callable $callwizard): void
    {
        $wizard = $this->createWizard(TestWizard::class);

        $callwizard($wizard);

        Event::assertDispatched(
            WizardLoaded::class,
            fn (WizardLoaded $e) => $e->wizard === $wizard
        );
    }

    public function afterSaveProvider(): Generator
    {
        yield from [
            'update' => [
                function (AbstractWizard $wizard) {
                    $wizard->update(new Request(), '1', 'step-with-view-data');
                },
            ],

            'show' => [
                function (AbstractWizard $wizard) {
                    $wizard->show(new Request(), '1', 'step-with-view-data');
                },
            ],
        ];
    }

    /** @test */
    public function it_can_be_deleted(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $wizard = $this->createWizard(TestWizard::class);

        $wizard->destroy(new Request(), '1');

        $wizard->show(new Request(), '1', 'step-name');
    }

    /** @test */
    public function it_redirects_to_the_default_route_after_the_wizard_has_been_deleted(): void
    {
        config(['arcanist.redirect_url' => '::redirect-url::']);

        $wizard = $this->createWizard(TestWizard::class);

        $response = new TestResponse($wizard->destroy(new Request(), '1'));

        $response->assertRedirect('::redirect-url::');
    }

    /** @test */
    public function it_redirects_to_the_correct_url_if_the_default_url_was_overwritten(): void
    {
        $wizard = $this->createWizard(SharedDataWizard::class);

        $response = new TestResponse($wizard->destroy(new Request(), '1'));

        $response->assertRedirect('::other-route::');
    }

    /**
     * @test
     * @dataProvider resumeWizardProvider
     */
    public function it_redirects_to_the_next_uncompleted_step_if_no_step_slug_was_given(callable $createwizard, string $expectedStep): void
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
            ]
        ];
    }

    /**
     * @test
     * @dataProvider errorWizardProvider
     */
    public function it_redirects_to_the_same_step_with_an_error_if_the_step_was_not_completed_successfully(callable $callWizard): void
    {
        $renderer = new FakeResponseRenderer();
        $wizard = $this->createWizard(ErrorWizard::class, renderer: $renderer);

        $callWizard($wizard);

        self::assertTrue(
            $renderer->didRedirectWithError(ErrorStep::class, '::error-message::')
        );
    }

    /** @test */
    public function it_redirects_back_to_last_step_with_an_error_if_the_action_was_not_successful(): void
    {
        $renderer = new FakeResponseRenderer();
        $resolver = m::mock(WizardActionResolver::class);
        $resolver->allows('resolveAction')
            ->andReturns(new class extends WizardAction {
                public function execute(mixed $payload): ActionResult
                {
                    return $this->failure('::message::');
                }
            });
        $wizard = $this->createWizard(TestWizard::class, renderer: $renderer, resolver: $resolver);

        $wizard->update(new Request(), '1', 'step-with-view-data');

        self::assertTrue(
            $renderer->didRedirectWithError(TestStepWithViewData::class, '::message::')
        );
    }

    public function errorWizardProvider()
    {
        yield from [
            'store' => [
                function (AbstractWizard $wizard) {
                    $wizard->store(new Request());
                }
            ],

            'update' => [
                function (AbstractWizard $wizard) {
                    $wizard->update(new Request(), '1', '::error-step::');
                }
            ]
        ];
    }

    /** @test */
    public function it_marks_a_step_as_completed_if_it_was_submitted_successfully_once(): void
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

    /** @test */
    public function it_does_not_mark_a_step_as_complete_if_it_failed(): void
    {
        $repo = $this->createWizardRepository(wizardClass: ErrorWizard::class);
        $wizard = $this->createWizard(ErrorWizard::class, repository: $repo);

        $wizard->update(new Request(), '1', '::error-step::');

        self::assertNull(
            $repo->loadData($wizard)['_arcanist']['::error-step::'] ?? null
        );
    }

    /** @test */
    public function it_merges_information_with_information_about_already_completed_steps(): void
    {
        $repo = $this->createWizardRepository([
            '_arcanist' => [
                'regular-step' => true,
            ]
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

    protected function onAfterComplete(ActionResult $result): RedirectResponse
    {
        $_SERVER['__onAfterComplete.called']++;

        return redirect()->back();
    }

    protected function beforeDelete(Request $request): void
    {
        $_SERVER['__beforeDelete.called']++;
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
        TestStepWithViewData::class
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

    protected function fields(): array
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
        $_SERVER['__beforeSaving.called']++;
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
