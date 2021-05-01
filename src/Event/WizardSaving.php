<?php declare(strict_types=1);

namespace Arcanist\Event;

use Arcanist\AbstractWizard;

final class WizardSaving
{
    public function __construct(public AbstractWizard $wizard)
    {
    }
}
