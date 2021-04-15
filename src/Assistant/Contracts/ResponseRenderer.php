<?php declare(strict_types=1);

namespace Sassnowski\Arcanist\Assistant\Contracts;

use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Support\Responsable;
use Sassnowski\Arcanist\Assistant\AssistantStep;
use Sassnowski\Arcanist\Assistant\AbstractAssistant;

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
