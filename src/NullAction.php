<?php declare(strict_types=1);

namespace Sassnowski\Arcanist;

use Sassnowski\Arcanist\Action\ActionResult;
use Sassnowski\Arcanist\Action\WizardAction;

final class NullAction extends WizardAction
{
    public function execute(mixed $payload): ActionResult
    {
        return $this->success();
    }
}
