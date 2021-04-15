<?php declare(strict_types=1);

namespace Tests\Fixtures;

use function redirect;
use Illuminate\Http\RedirectResponse;
use Sassnowski\Arcanist\AbstractAssistant;

class AssistantB extends AbstractAssistant
{
    protected function onAfterComplete(): RedirectResponse
    {
        return redirect();
    }
}
