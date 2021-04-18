<?php declare(strict_types=1);

namespace Sassnowski\Arcanist\Renderer;

use function redirect;
use Illuminate\View\Factory;
use Illuminate\Http\Response;
use Sassnowski\Arcanist\WizardStep;
use Illuminate\Http\RedirectResponse;
use Sassnowski\Arcanist\AbstractWizard;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\Support\Responsable;
use Sassnowski\Arcanist\Contracts\ResponseRenderer;

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

        return $this->factory->make($viewName, $data);
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
