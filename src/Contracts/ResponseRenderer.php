<?php declare(strict_types=1);

namespace Sassnowski\Arcanist\Contracts;

use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Sassnowski\Arcanist\WizardStep;
use Sassnowski\Arcanist\AbstractWizard;
use Illuminate\Contracts\Support\Responsable;

interface ResponseRenderer
{
    public function renderStep(
        WizardStep $step,
        AbstractWizard $wizard,
        array $data = []
    ): Response | Responsable;

    public function redirect(
        WizardStep $step,
        AbstractWizard $wizard
    ): RedirectResponse;
}
