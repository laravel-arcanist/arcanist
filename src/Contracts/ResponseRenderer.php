<?php declare(strict_types=1);

namespace Sassnowski\Arcanist\Contracts;

use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Sassnowski\Arcanist\AssistantStep;
use Sassnowski\Arcanist\AbstractAssistant;
use Illuminate\Contracts\Support\Responsable;

interface ResponseRenderer
{
    public function renderStep(
        AssistantStep $step,
        AbstractAssistant $assistant,
        array $data = []
    ): Response | Responsable;

    public function redirect(
        AssistantStep $step,
        AbstractAssistant $assistant
    ): RedirectResponse;
}
