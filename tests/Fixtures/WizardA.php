<?php declare(strict_types=1);

namespace Arcanist\Tests\Fixtures;

use function redirect;
use Arcanist\AbstractWizard;
use Illuminate\Http\RedirectResponse;

class WizardA extends AbstractWizard
{
    protected function onAfterComplete(\Arcanist\Action\ActionResult $result): RedirectResponse
    {
        return redirect();
    }
}
