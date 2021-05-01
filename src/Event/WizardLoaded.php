<?php declare(strict_types=1);

namespace Arcanist\Event;

use Arcanist\AbstractWizard;

final class WizardLoaded
{
    public function __construct(public AbstractWizard $wizard)
    {
    }
}
