<?php declare(strict_types=1);

namespace Sassnowski\Arcanist;

use function collect;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Validation\ValidatesRequests;

abstract class WizardStep
{
    use ValidatesRequests;

    /**
     * The name of the step that gets displayed in the step list.
     */
    public string $name = 'New Step';

    /**
     * The slug of the wizard that is used in the URL.
     */
    public string $slug = 'new-step';

    public function __construct(
        private AbstractWizard $wizard,
        private int $index
    ) {
    }

    /**
     * Returns the view data for the template.
     */
    public function viewData(Request $request): array
    {
        return [];
    }

    public function index(): int
    {
        return $this->index;
    }

    public function beforeSaving(Request $request, array $data): void
    {
    }

    /**
     * Checks if this step has already been completed.
     */
    abstract public function isComplete(): bool;

    /**
     * @throws ValidationException
     */
    public function process(Request $request): StepResult
    {
        $data = $this->validate($request, $this->rules());

        return $this->handle($request, $data);
    }

    protected function handle(Request $request, array $payload): StepResult
    {
        return $this->success($payload);
    }

    protected function fields(): array
    {
        return [];
    }

    protected function success(array $payload = []): StepResult
    {
        return StepResult::success($payload);
    }

    protected function error(?string $message = null): StepResult
    {
        return StepResult::failed($message);
    }

    /**
     * Checks if this step belongs to an existing wizard, i.e. a wizard
     * that has already been saved at least once.
     */
    protected function exists(): bool
    {
        return $this->wizard->exists();
    }

    protected function wizardId(): ?int
    {
        return $this->wizard->getId();
    }

    /**
     * Convenience method to include the fields specified in the `rules`
     * in the view data.
     */
    protected function withFormData(array $additionalData = []): array
    {
        return collect($this->rules())
            ->keys()
            ->map(fn (string $key) => explode('.', $key)[0])
            ->mapWithKeys(fn (string $key) => [
                $key => $this->data($key),
            ])->merge($additionalData)
            ->toArray();
    }

    protected function data(?string $key = null, mixed $default = null): mixed
    {
        return $this->wizard->data($key, $default);
    }

    protected function setData(string $key, mixed $value): void
    {
        $this->wizard->setData($key, $value);
    }

    /**
     * The validation rules for submitting the step's form.
     */
    private function rules(): array
    {
        return collect($this->fields())
            ->mapWithKeys(fn (Field $field) => [$field->getName() => $field->getRules()])
            ->all();
    }
}
