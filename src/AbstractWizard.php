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

namespace Arcanist;

use Arcanist\Action\ActionResult;
use Arcanist\Contracts\ResponseRenderer;
use Arcanist\Contracts\WizardActionResolver;
use Arcanist\Contracts\WizardRepository;
use Arcanist\Event\WizardFinished;
use Arcanist\Event\WizardFinishing;
use Arcanist\Event\WizardLoaded;
use Arcanist\Event\WizardSaving;
use Arcanist\Exception\CannotUpdateStepException;
use Arcanist\Exception\UnknownStepException;
use Arcanist\Exception\WizardNotFoundException;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use function collect;
use function config;
use function data_get;
use function event;
use function redirect;
use function route;

/**
 * @phpstan-type SummaryStep array{
 *      slug: string,
 *      isComplete: bool,
 *      title: string,
 *      active: bool,
 *      url: ?string
 * }
 */
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
     *
     * @var array<int, mixed>
     */
    protected array $steps = [];

    /**
     * The wizard's id in the database.
     *
     * @var null|int|string
     */
    protected mixed $id = null;

    /**
     * The index of the currently active step.
     */
    protected int $currentStep = 0;

    /**
     * The wizard's stored data.
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * URL to redirect to after the wizard was deleted.
     */
    protected string $redirectTo;

    /**
     * @var null|array<int, WizardStep>
     */
    private ?array $availableSteps = null;

    public function __construct(
        private WizardRepository $wizardRepository,
        protected ResponseRenderer $responseRenderer,
        private WizardActionResolver $actionResolver,
    ) {
        /** @var string $redirectTo */
        $redirectTo = config('arcanist.redirect_url', '/home');
        $this->redirectTo = $redirectTo;

        $this->steps = collect($this->steps)
            ->map(fn (string $step, $i): WizardStep => app($step)->init($this, $i))
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
     *
     * @return array<int, mixed>
     */
    public static function middleware(): array
    {
        return [];
    }

    /**
     * @return null|int|string
     */
    public function getId(): mixed
    {
        return $this->id;
    }

    /**
     * @param null|int|string $id
     */
    public function setId(mixed $id): void
    {
        $this->id = $id;
    }

    /**
     * Data that should be shared with every step in this
     * wizard. This data gets merged with a step's view data.
     *
     * @return array<string, mixed>
     */
    public function sharedData(Request $request): array
    {
        return [];
    }

    /**
     * Renders the template of the first step of this wizard.
     */
    public function create(Request $request): Responsable|Response|Renderable
    {
        return $this->renderStep($request, $this->availableSteps()[0]);
    }

    /**
     * Renders the template of the current step.
     *
     * @throws UnknownStepException
     */
    public function show(Request $request, string $wizardId, ?string $slug = null): Response|Responsable|Renderable
    {
        $this->load($wizardId);

        if (null === $slug) {
            return $this->responseRenderer->redirect(
                $this->firstIncompleteStep(),
                $this,
            );
        }

        $targetStep = $this->loadStep($slug);

        if (!$this->stepCanBeEdited($targetStep)) {
            return $this->responseRenderer->redirect(
                $this->firstIncompleteStep(),
                $this,
            );
        }

        return $this->renderStep($request, $targetStep);
    }

    /**
     * Handles the form submit for the first step in the workflow.
     *
     * @throws ValidationException
     */
    public function store(Request $request): Response|Responsable|Renderable
    {
        $step = $this->loadFirstStep();

        $result = $step->process($request);

        if (!$result->successful()) {
            return $this->responseRenderer->redirectWithError(
                $this->availableSteps()[0],
                $this,
                $result->error(),
            );
        }

        $this->saveStepData($step, $result->payload());

        return $this->responseRenderer->redirect(
            $this->availableSteps()[1],
            $this,
        );
    }

    /**
     * Handles the form submission of a step in an existing wizard.
     *
     * @throws CannotUpdateStepException
     * @throws UnknownStepException
     * @throws ValidationException
     */
    public function update(Request $request, string $wizardId, string $slug): Response|Responsable|Renderable
    {
        $this->load($wizardId);

        $step = $this->loadStep($slug);

        if (!$this->stepCanBeEdited($step)) {
            throw new CannotUpdateStepException();
        }

        $result = $step->process($request);

        if (!$result->successful()) {
            return $this->responseRenderer->redirectWithError(
                $this->steps[0],
                $this,
                $result->error(),
            );
        }

        $this->saveStepData(
            $step,
            $this->invalidateDependentFields($result->payload()),
        );

        return $this->isLastStep()
            ? $this->processLastStep($request, $step)
            : $this->responseRenderer->redirect(
                $this->nextStep(),
                $this,
            );
    }

    public function destroy(Request $request, string $wizardId): Response|Responsable|Renderable
    {
        $this->load($wizardId);

        $this->beforeDelete($request);

        $this->wizardRepository->deleteWizard($this);

        return $this->onAfterDelete();
    }

    /**
     * Fetch any previously stored data for this wizard.
     */
    public function data(?string $key = null, mixed $default = null): mixed
    {
        if (null === $key) {
            return Arr::except($this->data, '_arcanist');
        }

        return data_get($this->data, $key, $default);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * Checks if this wizard already exists or is being created
     * for the first time.
     */
    public function exists(): bool
    {
        return null !== $this->id;
    }

    /**
     * Returns a summary about the current wizard and its steps.
     *
     * @return array{
     *     id: string|int|null,
     *     slug: string,
     *     title: string,
     *     steps: array<int, SummaryStep>,
     * }
     */
    public function summary(): array
    {
        $current = $this->currentStep();

        return [
            'id' => $this->id,
            'slug' => static::$slug,
            'title' => $this->title(),
            'steps' => collect($this->availableSteps())->map(fn (WizardStep $step) => [
                'slug' => $step->slug,
                'isComplete' => $step->isComplete(),
                'title' => $step->title(),
                'active' => $step->index() === $current->index(),
                'url' => $this->exists()
                    ? route('wizard.' . static::$slug . '.show', [$this->getId(), $step->slug])
                    : null,
            ])->all(),
        ];
    }

    /**
     * Return a structured object of the wizard's data that will be
     * passed to the action after the wizard is completed.
     */
    protected function transformWizardData(Request $request): mixed
    {
        return $this->data();
    }

    /**
     * Gets called after the last step in the wizard is finished.
     */
    protected function onAfterComplete(ActionResult $result): Response|Responsable|Renderable
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
    }

    /**
     * Gets called after the wizard was deleted.
     */
    protected function onAfterDelete(): Response|Responsable|Renderable
    {
        return redirect()->to($this->redirectTo());
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

        if (null === $step) {
            throw new UnknownStepException(\sprintf(
                'No step with slug [%s] exists for wizard [%s]',
                $slug,
                static::class,
            ));
        }

        $this->currentStep = $step->index();

        return $step;
    }

    private function load(string $wizardId): void
    {
        $this->id = $wizardId;

        try {
            $this->data = $this->wizardRepository->loadData($this);
        } catch (WizardNotFoundException $e) {
            throw new NotFoundHttpException(previous: $e);
        }

        event(new WizardLoaded($this));
    }

    private function renderStep(Request $request, WizardStep $step): Responsable|Response|Renderable
    {
        return $this->responseRenderer->renderStep(
            $step,
            $this,
            $this->buildViewData($request, $step),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildViewData(Request $request, WizardStep $step): array
    {
        return \array_merge(
            $step->viewData($request),
            $this->sharedData($request),
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function saveStepData(WizardStep $step, array $data): void
    {
        event(new WizardSaving($this));

        $data['_arcanist'] = \array_merge(
            $this->data['_arcanist'] ?? [],
            [$step->slug => true],
            $data['_arcanist'] ?? [],
        );

        $this->wizardRepository->saveData($this, $data);
    }

    private function processLastStep(Request $request, WizardStep $step): Response|Responsable|Renderable
    {
        $this->load($this->id);

        event(new WizardFinishing($this));

        $result = $this->actionResolver
            ->resolveAction($this->onCompleteAction)
            ->execute($this->transformWizardData($request));

        if (!$result->successful()) {
            return $this->responseRenderer->redirectWithError(
                $step,
                $this,
                $result->error(),
            );
        }

        $response = $this->onAfterComplete($result);

        event(new WizardFinished($this));

        return $response;
    }

    private function nextStep(): WizardStep
    {
        return $this->availableSteps()[$this->currentStep + 1];
    }

    private function loadFirstStep(): WizardStep
    {
        return $this->availableSteps()[0];
    }

    private function currentStep(): WizardStep
    {
        return $this->availableSteps()[$this->currentStep ?? 0];
    }

    private function isLastStep(): bool
    {
        return $this->currentStep + 1 === \count($this->availableSteps());
    }

    private function firstIncompleteStep(): WizardStep
    {
        return collect($this->availableSteps())->first(fn (WizardStep $step) => !$step->isComplete(), $this->steps[count($this->steps) - 1]);
    }

    /**
     * @return array<int, WizardStep>
     */
    private function availableSteps(): array
    {
        if (null === $this->availableSteps) {
            $this->availableSteps = collect($this->steps)
                ->filter(fn (WizardStep $step): bool => !$step->omit())
                ->values()
                ->all();
        }

        return $this->availableSteps;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function invalidateDependentFields(array $payload): array
    {
        $changedFields = collect($payload)
            ->filter(fn (mixed $value, string $key) => $this->data($key) !== $value)
            ->keys()
            ->all();

        $fields = collect($this->availableSteps())
            ->mapWithKeys(fn (WizardStep $step) => [
                $step->slug => collect($step->dependentFields())
                    ->filter(fn (Field $field) => $field->shouldInvalidate($changedFields)),
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

    private function stepCanBeEdited(WizardStep $intendedStep): bool
    {
        if ($intendedStep->isComplete()) {
            return true;
        }

        /** @var WizardStep $firstIncompleteStep */
        $firstIncompleteStep = collect($this->availableSteps())
            ->first(fn (WizardStep $step) => !$step->isComplete());

        return $intendedStep->slug === $firstIncompleteStep->slug;
    }
}
