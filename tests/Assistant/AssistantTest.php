<?php declare(strict_types=1);

namespace Tests\Assistant;

use Generator;
use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Sassnowski\Arcanist\Assistant\AssistantStep;
use Sassnowski\Arcanist\Assistant\AbstractAssistant;
use Sassnowski\Arcanist\Assistant\Event\AssistantLoaded;
use Sassnowski\Arcanist\Assistant\Event\AssistantSaving;
use Sassnowski\Arcanist\Assistant\Event\AssistantFinished;
use Sassnowski\Arcanist\Assistant\Event\AssistantFinishing;
use Sassnowski\Arcanist\Assistant\Renderer\ResponseRenderer;
use Sassnowski\Arcanist\Assistant\Renderer\FakeResponseRenderer;
use Sassnowski\Arcanist\Assistant\Exception\UnknownStepException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sassnowski\Arcanist\Assistant\Repository\FakeAssistantRepository;

class AssistantTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
        $_SERVER['__beforeSaving.called'] = 0;
        $_SERVER['__onAfterComplete.called'] = 0;
        $_SERVER['__beforeDelete.called'] = 0;
    }

    /** @test */
    public function it_renders_the_first_step_in_an_assistant(): void
    {
        $renderer = new FakeResponseRenderer();
        $assistant = new TestAssistant(
            $this->createAssistantRepository(),
            $renderer
        );

        $assistant->create(new Request());

        self::assertTrue($renderer->stepWasRendered(TestStep::class));
    }

    /** @test */
    public function it_throws_an_exception_if_no_step_exists_for_the_provided_slug(): void
    {
        $this->expectException(UnknownStepException::class);

        $assistant = new TestAssistant(
            $this->createAssistantRepository(),
            new FakeResponseRenderer()
        );

        $assistant->show(new Request(), 1, '::step-slug::');
    }

    /** @test */
    public function it_gets_the_view_data_from_the_step(): void
    {
        $renderer = new FakeResponseRenderer();
        $assistant = new TestAssistant($this->createAssistantRepository(), $renderer);

        $assistant->show(new Request(), 1, 'step-with-view-data');

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
        $assistant = new TestAssistant(
            $this->createAssistantRepository(),
            new FakeResponseRenderer()
        );

        $assistant->store($request);
    }

    /** @test */
    public function it_handles_the_form_submit_for_the_first_step_in_the_workflow(): void
    {
        $request = Request::create('::url::', 'POST', [
            'first_name' => '::first-name::',
            'last_name' => '::last-name::',
        ]);
        $repo = new FakeAssistantRepository();
        $assistant = new TestAssistant($repo, new FakeResponseRenderer());

        $assistant->store($request);

        self::assertEquals(
            [
                'first_name' => '::first-name::',
                'last_name' => '::last-name::',
            ],
            $repo->loadData($assistant)
        );
    }

    /** @test */
    public function it_renders_a_step_for_an_existing_assistant_using_the_saved_data(): void
    {
        $repo = new FakeAssistantRepository([
            TestAssistant::class => [
                1 => [
                    'first_name' => '::first-name::',
                    'last_name' => '::last-name::',
                ],
            ],
        ]);
        $renderer = new FakeResponseRenderer();
        $assistant = new TestAssistant($repo, $renderer);

        $assistant->show(new Request(), 1, 'step-name');

        self::assertTrue($renderer->stepWasRendered(TestStep::class, [
            'first_name' => '::first-name::',
            'last_name' => '::last-name::',
        ]));
    }

    /** @test */
    public function it_handles_the_form_submission_for_a_step_in_an_existing_assistant(): void
    {
        $repo = $this->createAssistantRepository([
            'first_name' => '::old-first-name::',
            'last_name' => '::old-last-name::',
        ]);
        $request = Request::create('::url::', 'PUT', [
            'first_name' => '::new-first-name::',
            'last_name' => '::old-last-name::',
        ]);
        $assistant = new TestAssistant($repo, new FakeResponseRenderer());

        $assistant->update($request, 1, 'step-name');

        self::assertEquals([
            'first_name' => '::new-first-name::',
            'last_name' => '::old-last-name::',
        ], $repo->loadData($assistant));
    }

    /** @test */
    public function it_redirects_to_the_next_step_after_submitting_a_new_assistant(): void
    {
        $renderer = new FakeResponseRenderer();
        $request = Request::create('::url::', 'PUT', [
            'first_name' => '::new-first-name::',
            'last_name' => '::old-last-name::',
        ]);
        $assistant = new TestAssistant($this->createAssistantRepository(), $renderer);

        $assistant->store($request);

        self::assertTrue($renderer->didRedirectTo(TestStepWithViewData::class));
    }

    /** @test */
    public function it_redirects_to_the_next_step_after_submitting_an_existing_assistant(): void
    {
        $renderer = new FakeResponseRenderer();
        $request = Request::create('::url::', 'PUT', [
            'first_name' => '::new-first-name::',
            'last_name' => '::old-last-name::',
        ]);
        $assistant = new TestAssistant($this->createAssistantRepository(), $renderer);

        $assistant->update($request, 1, 'step-name');

        self::assertTrue($renderer->didRedirectTo(TestStepWithViewData::class));
    }

    /** @test */
    public function it_returns_the_assistants_title(): void
    {
        $assistant = new TestAssistant(
            $this->createAssistantRepository(),
            new FakeResponseRenderer()
        );

        $summary = $assistant->summary();

        self::assertEquals('::assistant-name::', $summary['title']);
    }

    /**
     * @test
     * @dataProvider idProvider
     */
    public function it_returns_the_assistants_id_in_the_summary(?int $id): void
    {
        $assistant = new TestAssistant(
            $this->createAssistantRepository(),
            new FakeResponseRenderer()
        );

        if ($id !== null) {
            $assistant->setId($id);
        }

        $summary = $assistant->summary();

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
    public function it_returns_the_assistants_slug_in_the_summary(): void
    {
        $assistant = new TestAssistant(
            $this->createAssistantRepository(),
            new FakeResponseRenderer()
        );

        $summary = $assistant->summary();

        self::assertEquals($assistant::$slug, $summary['slug']);
    }

    /** @test */
    public function it_returns_the_slug_of_each_step_in_the_summary(): void
    {
        $assistant = new TestAssistant(
            $this->createAssistantRepository(),
            new FakeResponseRenderer()
        );

        $summary = $assistant->summary();

        self::assertEquals('step-name', $summary['steps'][0]['slug']);
        self::assertEquals('step-with-view-data', $summary['steps'][1]['slug']);
    }

    /** @test */
    public function it_renders_information_about_the_completion_of_each_step(): void
    {
        $assistant = new TestAssistant(
            $this->createAssistantRepository(),
            new FakeResponseRenderer()
        );

        $summary = $assistant->summary();

        self::assertTrue($summary['steps'][0]['isComplete']);
        self::assertFalse($summary['steps'][1]['isComplete']);
    }

    /** @test */
    public function it_renders_the_title_of_each_step_in_the_summary(): void
    {
        $assistant = new TestAssistant(
            $this->createAssistantRepository(),
            new FakeResponseRenderer()
        );

        $summary = $assistant->summary();

        self::assertEquals('::step-1-name::', $summary['steps'][0]['name']);
        self::assertEquals('::step-2-name::', $summary['steps'][1]['name']);
    }

    /** @test */
    public function it_marks_the_first_step_as_active_on_the_create_route(): void
    {
        $assistant = new TestAssistant(
            $this->createAssistantRepository(),
            new FakeResponseRenderer()
        );
        $assistant->create(new Request());

        $summary = $assistant->summary();

        self::assertTrue($summary['steps'][0]['active']);
        self::assertFalse($summary['steps'][1]['active']);
    }

    /** @test */
    public function it_marks_the_current_step_active_for_the_show_route(): void
    {
        $assistant = new TestAssistant(
            $this->createAssistantRepository(),
            new FakeResponseRenderer()
        );
        $assistant->show(new Request(), 1, 'step-with-view-data');

        $summary = $assistant->summary();

        self::assertFalse($summary['steps'][0]['active']);
        self::assertTrue($summary['steps'][1]['active']);
    }

    /**
     * @test
     * @dataProvider assistantExistsProvider
     */
    public function it_can_check_if_an_existing_assistant_is_being_edited(?int $id, bool $expected): void
    {
        $assistant = new TestAssistant(
            $this->createAssistantRepository(),
            new FakeResponseRenderer()
        );

        if ($id !== null) {
            $assistant->setId($id);
        }

        self::assertEquals($expected, $assistant->exists());
    }

    public function assistantExistsProvider(): Generator
    {
        yield from [
            'does not exist' => [null, false],
            'exists' => [1, true],
        ];
    }

    /** @test */
    public function it_includes_the_link_to_the_step_in_the_summary(): void
    {
        $assistant = new TestAssistant(
            $this->createAssistantRepository(),
            new FakeResponseRenderer()
        );
        $assistant->setId(1);

        $summary = $assistant->summary();

        self::assertEquals('/assistant/assistant-name/1/step-name', $summary['steps'][0]['url']);
        self::assertEquals('/assistant/assistant-name/1/step-with-view-data', $summary['steps'][1]['url']);
    }

    /** @test */
    public function it_does_not_include_the_step_urls_if_the_assistant_does_not_exist(): void
    {
        $assistant = new TestAssistant(
            $this->createAssistantRepository(),
            new FakeResponseRenderer()
        );

        $summary = $assistant->summary();

        self::assertNull($summary['steps'][0]['url']);
        self::assertNull($summary['steps'][1]['url']);
    }

    /** @test */
    public function it_includes_the_assistants_cancel_button_text_in_the_summary(): void
    {
        $assistant = new TestAssistant(
            $this->createAssistantRepository(),
            new FakeResponseRenderer()
        );

        $summary = $assistant->summary();

        self::assertEquals('::cancel-text::', $summary['cancelText']);
    }

    /**
     * @test
     * @dataProvider sharedDataProvider
     */
    public function it_includes_shared_data_in_the_view_response(callable $callAssistant): void
    {
        $renderer = new FakeResponseRenderer();
        $repository = $this->createAssistantRepository(assistantClass:  SharedDataAssistant::class);
        $assistant = new SharedDataAssistant(
            $repository,
            $renderer
        );

        $callAssistant($assistant);

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
                function (AbstractAssistant $assistant) {
                    $assistant->create(new Request());
                }
            ],

            'show' => [
                function (AbstractAssistant $assistant) {
                    $assistant->show(new Request(), 1, 'step-name');
                }
            ]
        ];
    }

    /**
     * @test
     * @dataProvider beforeSaveProvider
     */
    public function it_calls_the_before_save_hook_of_the_step_before_saving_the_data(callable $callAssistant): void
    {
        $assistant = new TestAssistant(
            $this->createAssistantRepository(),
            new FakeResponseRenderer()
        );

        $callAssistant($assistant);

        self::assertEquals(1, $_SERVER['__beforeSaving.called']);
    }

    public function beforeSaveProvider(): Generator
    {
        $validRequest = Request::create('::uri::', 'POST', [
            'first_name' => '::first-name::',
            'last_name' => '::last-name::',
        ]);

        yield from  [
            'store' => [
                fn (AbstractAssistant $assistant) => $assistant->store($validRequest)
            ],
            'update' => [
                fn (AbstractAssistant $assistant) => $assistant->update($validRequest, 1, 'step-name')
            ]
        ];
    }

    /** @test */
    public function it_fires_an_event_after_the_last_step_of_the_assistant_was_finished(): void
    {
        $assistant = new TestAssistant(
            $this->createAssistantRepository(),
            new FakeResponseRenderer()
        );

        $assistant->update(new Request(), 1, 'step-with-view-data');

        Event::assertDispatched(
            AssistantFinishing::class,
            fn (AssistantFinishing $event) => $event->assistant === $assistant
        );
    }

    /** @test */
    public function it_fires_an_event_after_the_onComplete_callback_was_ran(): void
    {
        $assistant = new TestAssistant(
            $this->createAssistantRepository(),
            new FakeResponseRenderer()
        );

        $assistant->update(new Request(), 1, 'step-with-view-data');

        Event::assertDispatched(
            AssistantFinished::class,
            fn (AssistantFinished $event) => $event->assistant === $assistant
        );
    }

    /** @test */
    public function it_calls_the_on_after_complete_hook_of_the_assistant(): void
    {
        $assistant = new TestAssistant(
            $this->createAssistantRepository(),
            new FakeResponseRenderer()
        );

        $assistant->update(new Request(), 1, 'step-with-view-data');

        self::assertEquals(1, $_SERVER['__onAfterComplete.called']);
    }

    /** @test */
    public function it_stores_additional_data_that_was_set_during_the_request(): void
    {
        $repo = $this->createAssistantRepository();
        $assistant = new TestAssistant(
            $repo,
            new FakeResponseRenderer()
        );

        $assistant->update(new Request(), 1, 'step-with-view-data');

        self::assertEquals(['::key::' => '::value::'], $repo->loadData($assistant));
    }

    /**
     * @test
     * @dataProvider beforeSaveProvider
     */
    public function it_fires_an_event_before_the_assistant_gets_saved(callable $callAssistant): void
    {
        $assistant = new TestAssistant(
            $this->createAssistantRepository(),
            new FakeResponseRenderer()
        );

        $callAssistant($assistant);

        Event::assertDispatched(
            AssistantSaving::class,
            fn (AssistantSaving $e) => $e->assistant === $assistant
        );
    }

    /**
     * @test
     * @dataProvider afterSaveProvider
     */
    public function it_fires_an_event_after_an_assistant_was_loaded(callable $callAssistant): void
    {
        $assistant = new TestAssistant(
            $this->createAssistantRepository(),
            new FakeResponseRenderer()
        );

        $callAssistant($assistant);

        Event::assertDispatched(
            AssistantLoaded::class,
            fn (AssistantLoaded $e) => $e->assistant === $assistant
        );
    }

    public function afterSaveProvider(): Generator
    {
        yield from [
            'update' => [
                function (AbstractAssistant $assistant) {
                    $assistant->update(new Request(), 1, 'step-with-view-data');
                },
            ],

            'show' => [
                function (AbstractAssistant $assistant) {
                    $assistant->show(new Request(), 1, 'step-with-view-data');
                },
            ],
        ];
    }

    /** @test */
    public function it_can_be_deleted(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $assistant = new TestAssistant(
            $this->createAssistantRepository(),
            new FakeResponseRenderer()
        );

        $assistant->destroy(new Request(), 1);

        $assistant->show(new Request(), 1, 'step-name');
    }

    /** @test */
    public function it_redirects_to_the_default_route_after_the_assistant_has_been_deleted(): void
    {
        config(['arcanist.redirect_url' => '::redirect-url::']);

        $assistant = new TestAssistant(
            $this->createAssistantRepository(),
            new FakeResponseRenderer()
        );

        $response = new TestResponse($assistant->destroy(new Request(), 1));

        $response->assertRedirect('::redirect-url::');
    }

    /** @test */
    public function it_redirects_to_the_correct_url_if_the_default_url_was_overwritten(): void
    {
        $assistant = new SharedDataAssistant(
            $this->createAssistantRepository(assistantClass: SharedDataAssistant::class),
            new FakeResponseRenderer()
        );

        $response = new TestResponse($assistant->destroy(new Request(), 1));

        $response->assertRedirect('::other-route::');
    }

    /**
     * @test
     * @dataProvider resumeAssistantProvider
     */
    public function it_redirects_to_the_next_uncompleted_step_if_no_step_slug_was_given(callable $createAssistant, string $expectedStep): void
    {
        $renderer = new FakeResponseRenderer();
        $assistant = $createAssistant($renderer);

        $assistant->show(new Request(), 1);

        self::assertTrue($renderer->didRedirectTo($expectedStep));
    }

    public function resumeAssistantProvider(): Generator
    {
        yield from [
            [
                function (ResponseRenderer $renderer) {
                    return new TestAssistant(
                        $this->createAssistantRepository(),
                        $renderer
                    );
                },
                TestStepWithViewData::class,
            ],
            [
                function (ResponseRenderer $renderer) {
                    return new MultiStepAssistant(
                        $this->createAssistantRepository(assistantClass: MultiStepAssistant::class),
                        $renderer
                    );
                },
                TestStepWithViewData::class,
            ]
        ];
    }

    private function createAssistantRepository(array $data = [], ?string $assistantClass = null)
    {
        return new FakeAssistantRepository([
            $assistantClass ?: TestAssistant::class => [
                1 => $data
            ],
        ]);
    }
}

class TestAssistant extends AbstractAssistant
{
    public static string $slug = 'assistant-name';
    public static string $title = '::assistant-name::';

    protected array $steps = [
        TestStep::class,
        TestStepWithViewData::class,
    ];

    protected function onAfterComplete(): RedirectResponse
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

class MultiStepAssistant extends AbstractAssistant
{
    protected array $steps = [
        TestStep::class,
        DummyStep::class,
        TestStepWithViewData::class
    ];
}

class SharedDataAssistant extends AbstractAssistant
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

    protected function onAfterComplete(): RedirectResponse
    {
        return redirect();
    }

    protected function redirectTo(): string
    {
        return '::other-route::';
    }
}

class TestStep extends AssistantStep
{
    public string $name = '::step-1-name::';
    public string $slug = 'step-name';

    public function rules(): array
    {
        return [
            'first_name' => 'required',
            'last_name' => 'required',
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

class TestStepWithViewData extends AssistantStep
{
    public string $name = '::step-2-name::';
    public string $slug = 'step-with-view-data';

    public function viewData(Request $request): array
    {
        return ['foo' => 'bar'];
    }

    public function beforeSaving(Request $request, array $data): void
    {
        $this->setData('::key::', '::value::');
    }

    public function isComplete(): bool
    {
        return false;
    }
}

class DummyStep extends AssistantStep
{
    public function isComplete(): bool
    {
        return true;
    }
}
