<?php declare(strict_types=1);

namespace Arcanist\Renderer;

use function redirect;
use Arcanist\WizardStep;
use Arcanist\AbstractWizard;
use Illuminate\View\Factory;
use Illuminate\Http\Response;
use InvalidArgumentException;
use Illuminate\Http\RedirectResponse;
use Arcanist\Contracts\ResponseRenderer;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\Support\Responsable;
use Arcanist\Exception\StepTemplateNotFoundException;

class BladeResponseRenderer implements ResponseRenderer
{
    public function __construct(private Factory $factory, private string $viewBasePath)
    {
    }

    public function renderStep(
        WizardStep $step,
        AbstractWizard $wizard,
        array $data = []
    ): Response | Responsable | Renderable {
        $viewName = $this->viewBasePath . '.' . $wizard::$slug . '.' . $step->slug;

        try {
            return $this->factory->make($viewName, [
            'wizard' => $wizard->summary(),
            'data' => $data,
        ]);
        } catch (InvalidArgumentException) {
            throw StepTemplateNotFoundException::forStep($step);
        }
    }

    public function redirect(WizardStep $step, AbstractWizard $wizard): RedirectResponse
    {
        if (!$wizard->exists()) {
            return redirect()->route('wizard.' . $wizard::$slug . '.create');
        }

        return redirect()->route('wizard.' . $wizard::$slug . '.show', [
            $wizard->getId(), $step->slug
        ]);
    }

    public function redirectWithError(WizardStep $step, AbstractWizard $wizard, ?string $error = null): RedirectResponse
    {
        return redirect()
            ->route('wizard.' . $wizard::$slug . '.show', [
                $wizard->getId(),
                $step->slug,
            ])
            ->withErrors(['wizard' => $error]);
    }
}
