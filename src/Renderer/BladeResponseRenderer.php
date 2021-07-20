<?php declare(strict_types=1);

namespace Arcanist\Renderer;

use function redirect;
use Arcanist\WizardStep;
use Arcanist\AbstractWizard;
use Illuminate\View\Factory;
use InvalidArgumentException;
use Arcanist\Contracts\ResponseRenderer;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\Support\Responsable;
use Symfony\Component\HttpFoundation\Response;
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
            'step' => $data,
        ]);
        } catch (InvalidArgumentException) {
            throw StepTemplateNotFoundException::forStep($step);
        }
    }

    public function redirect(WizardStep $step, AbstractWizard $wizard): Response | Renderable | Responsable
    {
        if (!$wizard->exists()) {
            return redirect()->route('wizard.' . $wizard::$slug . '.create');
        }

        return redirect()->route('wizard.' . $wizard::$slug . '.show', [
            $wizard->getId(), $step->slug
        ]);
    }

    public function redirectWithError(
        WizardStep $step,
        AbstractWizard $wizard,
        ?string $error = null
    ): Response | Renderable | Responsable {
        return redirect()
            ->route('wizard.' . $wizard::$slug . '.show', [
                $wizard->getId(),
                $step->slug,
            ])
            ->withErrors(['wizard' => $error]);
    }
}
