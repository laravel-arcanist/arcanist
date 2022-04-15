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

namespace Arcanist\Tests\Fixtures;

use Arcanist\AbstractWizard;
use Illuminate\Http\RedirectResponse;
use function redirect;

class WizardB extends AbstractWizard
{
    protected function onAfterComplete(\Arcanist\Action\ActionResult $result): RedirectResponse
    {
        return redirect();
    }
}
