<?php declare(strict_types=1);

namespace Sassnowski\Arcanist\Exception;

use Exception;
use Sassnowski\Arcanist\WizardStep;

class StepTemplateNotFoundException extends Exception
{
    public static function forStep(WizardStep $step): self
    {
        return new self("No template found for step [{$step->slug}].");
    }
}
