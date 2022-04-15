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

namespace Arcanist\Action;

abstract class WizardAction
{
    abstract public function execute($payload): ActionResult;

    protected function success(array $payload = []): ActionResult
    {
        return ActionResult::success($payload);
    }

    protected function failure(?string $errorMessage = null): ActionResult
    {
        return ActionResult::failed($errorMessage);
    }
}
