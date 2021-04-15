<?php declare(strict_types=1);

namespace Sassnowski\Arcanist;

use function collect;
use Illuminate\Http\Request;

abstract class AssistantStep
{
    /**
     * The name of the step that gets displayed in the step list.
     */
    public string $name = 'New Step';

    /**
     * The slug of the assistant that is used in the URL.
     */
    public string $slug = 'new-step';

    public function __construct(
        private AbstractAssistant $assistant,
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

    /**
     * The validation rules for submitting the step's form.
     */
    public function rules(): array
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
     * Checks if this step belongs to an existing assistant, i.e. an assistant
     * that has already been saved at least once.
     */
    protected function exists(): bool
    {
        return $this->assistant->exists();
    }

    protected function assistantId(): ?int
    {
        return $this->assistant->getId();
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
        return $this->assistant->data($key, $default);
    }

    protected function setData(string $key, mixed $value): void
    {
        $this->assistant->setData($key, $value);
    }
}
