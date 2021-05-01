<?php declare(strict_types=1);

namespace Arcanist\Resolver;

use Arcanist\Action\WizardAction;
use Arcanist\Contracts\WizardActionResolver;

class ContainerWizardActionResolver implements WizardActionResolver
{
    public function resolveAction(string $actionClass): WizardAction
    {
        return app()->make($actionClass);
    }
}
