<?php declare(strict_types=1);

namespace Arcanist\Action;

use Illuminate\Http\Request;

abstract class WizardAction
{
    abstract public function execute(Request $request, $payload): ActionResult;

    protected function success(array $payload = []): ActionResult
    {
        return ActionResult::success($payload);
    }

    protected function failure(?string $errorMessage = null): ActionResult
    {
        return ActionResult::failed($errorMessage);
    }
}
