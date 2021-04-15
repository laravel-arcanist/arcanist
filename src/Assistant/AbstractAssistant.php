<?php declare(strict_types=1);

namespace Sassnowski\Arcanist\Assistant;

use function event;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Sassnowski\Arcanist\Assistant\Event\AssistantLoaded;
use Sassnowski\Arcanist\Assistant\Event\AssistantSaving;
use Sassnowski\Arcanist\Assistant\Event\AssistantFinished;
use Sassnowski\Arcanist\Assistant\Event\AssistantFinishing;
use Sassnowski\Arcanist\Assistant\Renderer\ResponseRenderer;
use Sassnowski\Arcanist\Assistant\Contracts\AssistantRepository;
use Sassnowski\Arcanist\Assistant\Exception\UnknownStepException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sassnowski\Arcanist\Assistant\Exception\AssistantNotFoundException;

abstract class AbstractAssistant
{
    use ValidatesRequests;

    /**
     * The display name of this assistant.
     */
    public static string $title = 'New Assistant';

    /**
     * The slug of this assistant that gets used in the URL.
     */
    public static string $slug = 'new-assistant';

    /**
     * The description of the assistant that gets shown in the action card.
     */
    public static string $description = 'A brand new assistant';

    /**
     * The action that gets executed after the last step of the
     * assistant is completed.
     */
    public string $onCompleteAction = NullAction::class;

    protected string $cancelText = 'Cancel assistant';

    /**
     * The steps this assistant consists of.
     */
    protected array $steps = [];

    /**
     * The assistant's id in the database.
     */
    protected ?int $id = null;

    /**
     * The index of the currently active step.
     */
    protected int $currentStep = 0;

    /**
     * The assistant's stored data.
     */
    protected array $data = [];

    /**
     * URL to redirect to after the assistant was deleted.
     */
    protected string $redirectTo;

    /**
     * Additional data that was set during the request. This will
     * be merged with $data before the assistant gets saved.
     */
    protected array $additionalData = [];

    public function __construct(
        private AssistantRepository $assistantRepository,
        private ResponseRenderer $responseRenderer
    ) {
        $this->redirectTo = config('arcanist.redirect_url', '/home');
        $this->steps = collect($this->steps)
            ->map(fn ($step, $i) => new $step($this, $i))
            ->all();
    }

    public static function startUrl(): string
    {
        $slug = static::$slug;

        return route("assistant.{$slug}.create");
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * Data that should be shared with every step in this
     * assistant. This data gets merged with a step's view data.
     */
    public function sharedData(Request $request): array
    {
        return [];
    }

    /**
     * Renders the template of the first step of this assistant.
     */
    public function create(Request $request): Responsable | Response
    {
        return $this->renderStep($request, $this->steps[0]);
    }

    /**
     * Renders the template of the current step.
     *
     * @throws UnknownStepException
     */
    public function show(Request $request, int $assistantId, ?string $slug = null): Responsable | Response | RedirectResponse
    {
        $this->load($assistantId);

        if ($slug === null) {
            /** @var AssistantStep $lastCompletedStep */
            $lastCompletedStep = collect($this->steps)
                ->last(fn (AssistantStep $s) => $s->isComplete());

            return $this->responseRenderer->redirect(
                $this->steps[$lastCompletedStep->index() + 1],
                $this
            );
        }

        return $this->renderStep($request, $this->loadStep($slug));
    }

    /**
     * Handles the form submit for the first step in the workflow.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $step = $this->loadFirstStep();

        $data = $this->validate($request, $step->rules());

        $this->saveStepData($step, $data, $request);

        return $this->responseRenderer->redirect(
            $this->steps[1],
            $this
        );
    }

    /**
     * Handles the form submission of a step in an existing assistant.
     *
     * @throws UnknownStepException
     * @throws ValidationException
     */
    public function update(Request $request, int $assistantId, string $slug): RedirectResponse
    {
        $this->load($assistantId);

        $step = $this->loadStep($slug);
        $data = $this->validate($request, $step->rules());

        $this->saveStepData($step, $data, $request);

        if ($this->isLastStep()) {
            event(new AssistantFinishing($this));

            $response = $this->onAfterComplete();

            event(new AssistantFinished($this));

            return $response;
        }

        return $this->responseRenderer->redirect(
            $this->nextStep(),
            $this
        );
    }

    public function destroy(Request $request, int $assistantId): RedirectResponse
    {
        $this->load($assistantId);

        $this->beforeDelete($request);

        $this->assistantRepository->deleteAssistant($this);

        return redirect()->to($this->redirectTo());
    }

    public function setData(string $key, mixed $value): void
    {
        $this->additionalData[$key] = $value;
    }

    /**
     * Fetch any previously stored data for this assistant.
     */
    public function data(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return array_merge($this->data, $this->additionalData);
        }

        return data_get($this->additionalData, $key) ?: data_get($this->data, $key, $default);
    }

    /**
     * Checks if this assistant already exists or is being created
     * for the first time.
     */
    public function exists(): bool
    {
        return $this->id !== null;
    }

    /**
     * Returns a summary about the current assistant and its steps.
     */
    public function summary(): array
    {
        $current = $this->currentStep();

        return [
            'id' => $this->id,
            'slug' => static::$slug,
            'title' => $this->title(),
            'cancelText' => $this->cancelText(),
            'steps' => collect($this->steps)->map(fn (AssistantStep $step) => [
                'slug' => $step->slug,
                'isComplete' => $step->isComplete(),
                'name' => $step->name,
                'active' => $step->index() === $current->index(),
                'url' => $this->exists()
                    ? '/assistant/' . static::$slug . '/' . $this->id . '/' . $step->slug
                    : null,
            ])->all()
        ];
    }

    /**
     * Return a structured object of the assistant's data that will be
     * passed to the action after the assistant is completed.
     */
    public function transformAssistantData(): mixed
    {
        return $this->data();
    }

    /**
     * Gets called after the last step in the assistant is finished.
     */
    protected function onAfterComplete(): RedirectResponse
    {
        return redirect()->to($this->redirectTo());
    }

    /**
     * Hook that gets called before the assistant is deleted. This is
     * a good place to free up any resources that might have been
     * reserved by the assistant.
     */
    protected function beforeDelete(Request $request): void
    {
        //
    }

    protected function redirectTo(): string
    {
        return $this->redirectTo;
    }

    /**
     * Returns the assistant's title that gets displayed in the frontend.
     */
    protected function title(): string
    {
        return static::$title;
    }

    protected function cancelText(): string
    {
        return $this->cancelText;
    }

    /**
     * @throws UnknownStepException
     */
    private function loadStep(string $slug): AssistantStep
    {
        /** @var ?AssistantStep $step */
        $step = collect($this->steps)
            ->first(fn (AssistantStep $step) => $step->slug === $slug);

        if ($step === null) {
            throw new UnknownStepException(sprintf(
                'No step with slug [%s] exists for assistant [%s]',
                $slug,
                static::class,
            ));
        }

        $this->currentStep = $step->index();

        return $step;
    }

    private function load(int $assistantId): void
    {
        $this->id = $assistantId;

        try {
            $this->data = $this->assistantRepository->loadData($this);
        } catch (AssistantNotFoundException $e) {
            throw new NotFoundHttpException(previous: $e);
        }

        event(new AssistantLoaded($this));
    }

    private function renderStep(Request $request, AssistantStep $step): Responsable | Response
    {
        return $this->responseRenderer->renderStep(
            $step,
            $this,
            $this->buildViewData($request, $step)
        );
    }

    private function buildViewData(Request $request, AssistantStep $step): array
    {
        return array_merge(
            $step->viewData($request),
            $this->sharedData($request)
        );
    }

    private function saveStepData(AssistantStep $step, array $data, Request $request): void
    {
        event(new AssistantSaving($this));

        $step->beforeSaving($request, $data);

        $data = array_merge($data, $this->additionalData);

        $this->assistantRepository->saveData($this, $data);
    }

    private function nextStep(): AssistantStep
    {
        return $this->steps[$this->currentStep + 1];
    }

    private function loadFirstStep(): AssistantStep
    {
        return $this->steps[0];
    }

    private function currentStep(): AssistantStep
    {
        return $this->steps[$this->currentStep ?? 0];
    }

    private function isLastStep(): bool
    {
        return $this->currentStep + 1 === count($this->steps);
    }
}
