<?php declare(strict_types=1);

namespace Sassnowski\Arcanist\Event;

use Sassnowski\Arcanist\AbstractWizard;

final class WizardFinished
{
    public function __construct(public AbstractWizard $wizard)
    {
    }
}
