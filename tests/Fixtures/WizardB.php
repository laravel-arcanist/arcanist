<?php declare(strict_types=1);

namespace Tests\Fixtures;

use function redirect;
use Illuminate\Http\RedirectResponse;
use Sassnowski\Arcanist\AbstractWizard;

class WizardB extends AbstractWizard
{
    protected function onAfterComplete(\Sassnowski\Arcanist\Action\ActionResult $result): RedirectResponse
    {
        return redirect();
    }
}
