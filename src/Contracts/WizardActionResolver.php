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

namespace Arcanist\Contracts;

use Arcanist\Action\WizardAction;

interface WizardActionResolver
{
    public function resolveAction(string $actionClass): WizardAction;
}
