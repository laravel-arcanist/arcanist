<?php declare(strict_types=1);

namespace Sassnowski\Arcanist;

use function event;
use function route;
use function config;
use function collect;
use function data_get;
use function redirect;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Sassnowski\Arcanist\Event\WizardLoaded;
use Sassnowski\Arcanist\Event\WizardSaving;
use Illuminate\Contracts\Support\Renderable;
use Sassnowski\Arcanist\Action\ActionResult;
use Illuminate\Contracts\Support\Responsable;
use Sassnowski\Arcanist\Event\WizardFinished;
use Illuminate\Validation\ValidationException;
use Sassnowski\Arcanist\Event\WizardFinishing;
use Sassnowski\Arcanist\Contracts\ResponseRenderer;
use Sassnowski\Arcanist\Contracts\WizardRepository;
use Sassnowski\Arcanist\Contracts\WizardActionResolver;
use Sassnowski\Arcanist\Exception\UnknownStepException;
use Sassnowski\Arcanist\Exception\WizardNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AbstractWizard
{
    /**
     * The display name of this wizard.
     */
    public static string $title = 'New wizard';

    /**
     * The slug of this wizard that gets used in the URL.
     */
    public static string $slug = 'new-wizard';

    /**
     * The description of the wizard that gets shown in the action card.
     */
    public static string $description = 'A brand new wizard';

    /**
     * The action that gets executed after the last step of the
     * wizard is completed.
     */
    protected string $onCompleteAction = NullAction::class;

    /**
     * The steps this wizard consists of.
     */
    protected array $steps = [];

    /**
     * The wizard's id in the database.
     */
    protected mixed $id = null;

    /**
     * The index of the currently active step.
     */
    protected int $currentStep = 0;

    /**
     * The wizard's stored data.
     */
    protected array $data = [];

    /**
     * URL to redirect to after the wizard was deleted.
     */
    protected string $redirectTo;

    public function __construct(
        private WizardRepository $wizardRepository,
        private ResponseRenderer $responseRenderer,
        private WizardActionResolver $actionResolver
    ) {
        $this->redirectTo = config('arcanist.redirect_url', '/home');
        $this->steps = collect($this->steps)
            ->map(fn ($step, $i) => app($step)->init($this, $i))
            ->all();
    }

    public static function startUrl(): string
    {
        $slug = static::$slug;

        return route("wizard.{$slug}.create");
    }

    /**
     * Here you can define additional middleware for a wizard
     * that gets merged together with the global middleware
     * defined in the config.
     */
    public static function middleware(): array
    {
        return [];
    }

    /**
     * This method is called when a wizard has expired, i.e. hasn't
     * been updated in longer than the configured TTL. This is a good
     * place to perform any cleanup steps necessary, such as deleting
     * uploaded images or freeing resources that might have been allocated.
     */
    public static function onExpire(array $data): void
    {
    }

    public function getId(): mixed
    {
        return $this->id;
    }

    public function setId(mixed $id): void
    {
        $this->id = $id;
    }

    /**
     * Data that should be shared with every step in this
     * wizard. This data gets merged with a step's view data.
     */
    public function sharedData(Request $request): array
    {
        return [];
    }

    /**
     * Renders the template of the first step of this wizard.
     */
    public function create(Request $request): Responsable | Response | Renderable
    {
        return $this->renderStep($request, $this->steps[0]);
    }

    /**
     * Renders the template of the current step.
     *
     * @throws UnknownStepException
     */
    public function show(Request $request, int $wizardId, ?string $slug = null): Responsable | Response | Renderable | RedirectResponse
    {
        $this->load($wizardId);

        if ($slug === null) {
            /** @var WizardStep $lastCompletedStep */
            $lastCompletedStep = collect($this->steps)
                ->last(fn (WizardStep $s) => $s->isComplete());

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

        $result = $step->process($request);

        if (!$result->successful()) {
            return $this->responseRenderer->redirectWithError(
                $this->steps[0],
                $this,
                $result->error()
            );
        }

        $this->saveStepData($step, $result->payload());

        return $this->responseRenderer->redirect(
            $this->steps[1],
            $this
        );
    }

    /**
     * Handles the form submission of a step in an existing wizard.
     *
     * @throws UnknownStepException
     * @throws ValidationException
     */
    public function update(Request $request, int $wizardId, string $slug): RedirectResponse
    {
        $this->load($wizardId);

        $step = $this->loadStep($slug);

        $result = $step->process($request);

        if (!$result->successful()) {
            return $this->responseRenderer->redirectWithError(
                $this->steps[0],
                $this,
                $result->error()
            );
        }

        $this->saveStepData(
            $step,
            $this->invalidateDependentFields($result->payload())
        );

        return $this->isLastStep()
            ? $this->processLastStep($step)
            : $this->responseRenderer->redirect(
                $this->nextStep(),
                $this
            );
    }

    public function destroy(Request $request, int $wizardId): RedirectResponse
    {
        $this->load($wizardId);

        $this->beforeDelete($request);

        $this->wizardRepository->deleteWizard($this);

        return redirect()->to($this->redirectTo());
    }

    /**
     * Fetch any previously stored data for this wizard.
     */
    public function data(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->data;
        }

        return data_get($this->data, $key, $default);
    }

    /**
     * Checks if this wizard already exists or is being created
     * for the first time.
     */
    public function exists(): bool
    {
        return $this->id !== null;
    }

    /**
     * Returns a summary about the current wizard and its steps.
     */
    public function summary(): array
    {
        $current = $this->currentStep();

        return [
            'id' => $this->id,
            'slug' => static::$slug,
            'title' => $this->title(),
            'steps' => collect($this->steps)->map(fn (WizardStep $step) => [
                'slug' => $step->slug,
                'isComplete' => $step->isComplete(),
                'name' => $step->name,
                'active' => $step->index() === $current->index(),
                'url' => $this->exists()
                    ? route('wizard.' . static::$slug . '.show', [$this->getId(), $step->slug])
                    : null,
            ])->all()
        ];
    }

    /**
     * Return a structured object of the wizard's data that will be
     * passed to the action after the wizard is completed.
     */
    protected function transformWizardData(): mixed
    {
        return $this->data();
    }

    /**
     * Gets called after the last step in the wizard is finished.
     */
    protected function onAfterComplete(ActionResult $result): RedirectResponse
    {
        return redirect()->to($this->redirectTo());
    }

    /**
     * Hook that gets called before the wizard is deleted. This is
     * a good place to free up any resources that might have been
     * reserved by the wizard.
     */
    protected function beforeDelete(Request $request): void
    {
        //
    }

    /**
     * The route that gets redirected to after completing the last
     * step of the wizard.
     */
    protected function redirectTo(): string
    {
        return $this->redirectTo;
    }

    /**
     * Returns the wizard's title that gets displayed in the frontend.
     */
    protected function title(): string
    {
        return static::$title;
    }

    /**
     * @throws UnknownStepException
     */
    private function loadStep(string $slug): WizardStep
    {
        /** @var ?WizardStep $step */
        $step = collect($this->steps)
            ->first(fn (WizardStep $step) => $step->slug === $slug);

        if ($step === null) {
            throw new UnknownStepException(sprintf(
                'No step with slug [%s] exists for wizard [%s]',
                $slug,
                static::class,
            ));
        }

        $this->currentStep = $step->index();

        return $step;
    }

    private function load(int $wizardId): void
    {
        $this->id = $wizardId;

        try {
            $this->data = $this->wizardRepository->loadData($this);
        } catch (WizardNotFoundException $e) {
            throw new NotFoundHttpException(previous: $e);
        }

        event(new WizardLoaded($this));
    }

    private function renderStep(Request $request, WizardStep $step): Responsable | Response | Renderable
    {
        return $this->responseRenderer->renderStep(
            $step,
            $this,
            $this->buildViewData($request, $step)
        );
    }

    private function buildViewData(Request $request, WizardStep $step): array
    {
        return array_merge(
            $step->viewData($request),
            $this->sharedData($request)
        );
    }

    private function saveStepData(WizardStep $step, array $data): void
    {
        event(new WizardSaving($this));

        $data['_arcanist'] = array_merge(
            $this->data['_arcanist'] ?? [],
            [$step->slug => true],
            $data['_arcanist'] ?? []
        );

        $this->wizardRepository->saveData($this, $data);
    }

    private function processLastStep(WizardStep $step): RedirectResponse
    {
        event(new WizardFinishing($this));

        $result = $this->actionResolver
            ->resolveAction($this->onCompleteAction)
            ->execute($this->transformWizardData());

        if (!$result->successful()) {
            return $this->responseRenderer->redirectWithError(
                $step,
                $this,
                $result->error()
            );
        }

        $response = $this->onAfterComplete($result);

        event(new WizardFinished($this));

        return $response;
    }

    private function nextStep(): WizardStep
    {
        return $this->steps[$this->currentStep + 1];
    }

    private function loadFirstStep(): WizardStep
    {
        return $this->steps[0];
    }

    private function currentStep(): WizardStep
    {
        return $this->steps[$this->currentStep ?? 0];
    }

    private function isLastStep(): bool
    {
        return $this->currentStep + 1 === count($this->steps);
    }

    private function invalidateDependentFields(array $payload): array
    {
        $changedFields = collect($payload)
            ->filter(fn (mixed $value, string $key) => $this->data($key) !== $value)
            ->keys()
            ->all();

        $fields = collect($this->steps)
            ->mapWithKeys(fn (WizardStep $step) => [
                $step->slug => collect($step->dependentFields())
                    ->filter(fn (Field $field) => $field->shouldInvalidate($changedFields))
            ])
            ->filter(fn (Collection $fields) => $fields->isNotEmpty());

        // Mark all steps that had at least one of their fields
        // invalidated as incomplete.
        $payload = $fields->keys()
            ->reduce(function (array $payload, string $stepSlug) {
                $payload['_arcanist'][$stepSlug] = null;

                return $payload;
            }, $payload);

        // Unset data for all fields that should be invalidated.
        return $fields->values()
            ->flatten()
            ->map->name
            ->unique()
            ->reduce(function (array $payload, string $fieldName) {
                $payload[$fieldName] = null;

                return $payload;
            }, $payload);
    }
}
