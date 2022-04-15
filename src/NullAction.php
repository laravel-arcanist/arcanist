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

namespace Arcanist;

use Arcanist\Action\ActionResult;
use Arcanist\Action\WizardAction;

final class NullAction extends WizardAction
{
    public function execute(mixed $payload): ActionResult
    {
        return $this->success();
    }
}
