<?php declare(strict_types=1);

namespace Arcanist\Event;

use Arcanist\AbstractWizard;

final class WizardFinishing
{
    public function __construct(public AbstractWizard $wizard)
    {
    }
}
