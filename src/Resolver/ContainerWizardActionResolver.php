<?php declare(strict_types=1);

namespace Sassnowski\Arcanist\Resolver;

use Sassnowski\Arcanist\Action\WizardAction;
use Sassnowski\Arcanist\Contracts\WizardActionResolver;

class ContainerWizardActionResolver implements WizardActionResolver
{
    public function resolveAction(string $actionClass): WizardAction
    {
        return app()->make($actionClass);
    }
}
