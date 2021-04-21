<?php declare(strict_types=1);

namespace Sassnowski\Arcanist\Contracts;

use Sassnowski\Arcanist\WizardAction;

interface WizardActionResolver
{
    public function resolveAction(string $actionClass): WizardAction;
}
