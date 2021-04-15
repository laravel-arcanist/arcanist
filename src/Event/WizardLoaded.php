<?php declare(strict_types=1);

namespace Sassnowski\Arcanist\Event;

use Sassnowski\Arcanist\AbstractWizard;

final class WizardLoaded
{
    public function __construct(public AbstractWizard $wizard)
    {
    }
}
