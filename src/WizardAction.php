<?php declare(strict_types=1);

namespace Sassnowski\Arcanist;

use Sassnowski\Arcanist\Action\ActionResult;

abstract class WizardAction
{
    abstract public function execute(mixed $payload): ActionResult;

    protected function success(array $payload = []): ActionResult
    {
        return ActionResult::success($payload);
    }

    protected function failure(?string $errorMessage = null): ActionResult
    {
        return ActionResult::failed($errorMessage);
    }
}
