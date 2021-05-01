<?php declare(strict_types=1);

namespace Arcanist\Event;

use Arcanist\AbstractWizard;

final class WizardFinished
{
    public function __construct(public AbstractWizard $wizard)
    {
    }
}
