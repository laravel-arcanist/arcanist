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

namespace Arcanist\Contracts;

use Arcanist\AbstractWizard;
use Arcanist\WizardStep;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\Support\Responsable;
use Statamic\View\View;
use Symfony\Component\HttpFoundation\Response;

interface ResponseRenderer
{
    /**
     * @param array<string, mixed> $data
     */
    public function renderStep(
        WizardStep $step,
        AbstractWizard $wizard,
        array $data = [],
    ): Responsable|Response|Renderable|View;

    public function redirect(
        WizardStep $step,
        AbstractWizard $wizard,
    ): Responsable|Response|Renderable|View;

    public function redirectWithError(
        WizardStep $step,
        AbstractWizard $wizard,
        ?string $error = null,
    ): Responsable|Response|Renderable|View;
}
