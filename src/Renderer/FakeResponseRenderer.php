<?php declare(strict_types=1);

namespace Sassnowski\Arcanist\Renderer;

use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Sassnowski\Arcanist\AssistantStep;
use Sassnowski\Arcanist\AbstractAssistant;
use Illuminate\Contracts\Support\Responsable;
use Sassnowski\Arcanist\Contracts\ResponseRenderer;

class FakeResponseRenderer implements ResponseRenderer
{
    private array $renderedSteps = [];
    private ?string $redirect = null;

    public function renderStep(
        AssistantStep $step,
        AbstractAssistant $assistant,
        array $data = []
    ): Response | Responsable {
        $this->renderedSteps[get_class($step)] = $data;

        return new Response();
    }

    public function redirect(AssistantStep $step, AbstractAssistant $assistant): RedirectResponse
    {
        $this->redirect = get_class($step);

        return new RedirectResponse('::url::');
    }

    public function stepWasRendered(string $stepClass, ?array $data = null): bool
    {
        if (!isset($this->renderedSteps[$stepClass])) {
            return false;
        }

        if ($data !== null) {
            return array_diff($data, $this->renderedSteps[$stepClass]) === [];
        }

        return true;
    }

    public function didRedirectTo(string $stepClass): bool
    {
        return $this->redirect === $stepClass;
    }
}
