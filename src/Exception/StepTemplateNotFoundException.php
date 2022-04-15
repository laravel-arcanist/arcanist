<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/laravel-arcanist/arcanist
 */

namespace Arcanist\Exception;

use Arcanist\WizardStep;
use Exception;
use Facade\IgnitionContracts\BaseSolution;
use Facade\IgnitionContracts\ProvidesSolution;
use Facade\IgnitionContracts\Solution;

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
