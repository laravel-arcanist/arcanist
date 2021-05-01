<?php declare(strict_types=1);

namespace Arcanist\Contracts;

use Arcanist\Action\WizardAction;

interface WizardActionResolver
{
    public function resolveAction(string $actionClass): WizardAction;
}
