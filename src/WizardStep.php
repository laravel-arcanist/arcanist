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

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use function collect;

abstract class WizardStep
{
    use ValidatesRequests;

    /**
     * The name of the step that gets displayed in the step list.
     */
    public string $title = 'New Step';

    /**
     * The slug of the wizard that is used in the URL.
     */
    public string $slug = 'new-step';
    private AbstractWizard $wizard;
    private int $index;

    public function init(AbstractWizard $wizard, int $index): self
    {
        // @TODO: I _hate_ this but I want to "free up" the constructor to enable dependency
        // injection in the step. All other solution would take quite a bit more time to
        // implement than I currently want to invest.
        $this->wizard = $wizard;
        $this->index = $index;

        return $this;
    }

    public function title(): string
    {
        return $this->title;
    }

    /**
     * Returns the view data for the template.
     *
     * @return array<string, mixed>
     */
    public function viewData(Request $request): array
    {
        return $this->withFormData();
    }

    public function index(): int
    {
        return $this->index;
    }

    /**
     * Checks if this step has already been completed.
     */
    public function isComplete(): bool
    {
        return (bool) $this->data("_arcanist.{$this->slug}", false);
    }

    /**
     * Checks if the step should be omitted from the wizard.
     */
    public function omit(): bool
    {
        return false;
    }

    /**
     * @throws ValidationException
     */
    public function process(Request $request): StepResult
    {
        $data = $this->validate($request, $this->rules(), $this->messages(), $this->customAttributes());

        return collect($this->fields())
            ->mapWithKeys(fn (Field $field) => [
                $field->name => $field->value($data[$field->name] ?? null),
            ])
            ->pipe(fn (Collection $values) => $this->handle($request, $values->toArray()));
    }

    /**
     * @return array<int, Field>
     */
    public function dependentFields(): array
    {
        return collect($this->fields())
            ->filter(fn (Field $field) => \count($field->dependencies) > 0)
            ->all();
    }

    /**
     * @return array<int, Field>
     */
    public function fields(): array
    {
        return [];
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function handle(Request $request, array $payload): StepResult
    {
        return $this->success($payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
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

    /**
     * Convenience method to include the fields specified in the `rules`
     * in the view data.
     *
     * @param array<string, mixed> $additionalData
     *
     * @return array<string, mixed>
     */
    protected function withFormData(array $additionalData = []): array
    {
        return collect($this->rules())
            ->keys()
            ->map(fn (string $key) => \explode('.', $key)[0])
            ->mapWithKeys(fn (string $key) => [
                $key => $this->data($key),
            ])->merge($additionalData)
            ->toArray();
    }

    protected function data(?string $key = null, mixed $default = null): mixed
    {
        return $this->wizard->data($key, $default);
    }

    /**
     * The validation rules for submitting the step's form.
     *
     * @return array<string, array<int, Rule|string>>
     */
    protected function rules(): array
    {
        return collect($this->fields())
            ->mapWithKeys(fn (Field $field) => [$field->name => $field->rules])
            ->all();
    }

    /**
     * The custom validation messages
     *
     * @return array<string, string>
     */
    protected function messages(): array {
        return [];
    }

    /**
     * The custom validation messages
     *
     * @return array<string, string>
     */
    protected function customAttributes(): array {
        return [];
    }
}
