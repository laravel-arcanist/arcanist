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

namespace Arcanist\Event;

use Arcanist\AbstractWizard;

final class WizardFinishing
{
    public function __construct(public AbstractWizard $wizard)
    {
    }
}
