<?php declare(strict_types=1);

namespace Arcanist\Exception;

use Exception;
use Arcanist\WizardStep;
use Facade\IgnitionContracts\Solution;
use Facade\IgnitionContracts\BaseSolution;
use Facade\IgnitionContracts\ProvidesSolution;

class StepTemplateNotFoundException extends Exception implements ProvidesSolution
{
    public function __construct(private WizardStep $step)
    {
        parent::__construct("No template found for step [{$this->step->slug}].");
    }

    public static function forStep(WizardStep $step): self
    {
        return new self($step);
    }

    public function getSolution(): Solution
    {
        return BaseSolution::create('No template for wizard step found')
            ->setSolutionDescription("No template was found for the step [{$this->step->title}].");
    }
}
