<?php declare(strict_types=1);

namespace Tests\Fixtures;

use function redirect;
use Arcanist\AbstractWizard;
use Illuminate\Http\RedirectResponse;

class WizardB extends AbstractWizard
{
    protected function onAfterComplete(\Arcanist\Action\ActionResult $result): RedirectResponse
    {
        return redirect();
    }
}
