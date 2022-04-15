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
use Symfony\Component\HttpFoundation\Response;

interface ResponseRenderer
{
    public function renderStep(
        WizardStep $step,
        AbstractWizard $wizard,
        array $data = [],
    ): Response|Responsable|Renderable;

    public function redirect(
        WizardStep $step,
        AbstractWizard $wizard,
    ): Response|Responsable|Renderable;

    public function redirectWithError(
        WizardStep $step,
        AbstractWizard $wizard,
        ?string $error = null,
    ): Response|Responsable|Renderable;
}
