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

namespace Arcanist\Renderer;

use Arcanist\AbstractWizard;
use Arcanist\Contracts\ResponseRenderer;
use Arcanist\WizardStep;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class FakeResponseRenderer implements ResponseRenderer
{
    /**
     * @var array<class-string<WizardStep>, array<string, mixed>>
     */
    private array $renderedSteps = [];
    private ?string $redirect = null;
    private ?string $error = null;
    private bool $hasError = false;

    /**
     * @param array<string, mixed> $data
     */
    public function renderStep(
        WizardStep $step,
        AbstractWizard $wizard,
        array $data = [],
    ): Response|Responsable {
        $this->renderedSteps[$step::class] = $data;

        return new Response();
    }

    public function redirect(WizardStep $step, AbstractWizard $wizard): RedirectResponse
    {
        $this->redirect = $step::class;

        return new RedirectResponse('::url::');
    }

    public function redirectWithError(
        WizardStep $step,
        AbstractWizard $wizard,
        ?string $error = null,
    ): RedirectResponse {
        $this->redirect = $step::class;
        $this->hasError = true;
        $this->error = $error;

        return new RedirectResponse('::url::');
    }

    /**
     * @param null|array<string, mixed> $data
     */
    public function stepWasRendered(string $stepClass, ?array $data = null): bool
    {
        if (!isset($this->renderedSteps[$stepClass])) {
            return false;
        }

        if (null !== $data) {
            return \array_diff($data, $this->renderedSteps[$stepClass]) === [];
        }

        return true;
    }

    public function didRedirectTo(string $stepClass): bool
    {
        return $this->redirect === $stepClass && !$this->hasError;
    }

    public function didRedirectWithError(string $stepClass, ?string $message = null): bool
    {
        return $this->redirect === $stepClass
            && $this->hasError
            && $this->error === $message;
    }
}
