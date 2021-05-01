<?php declare(strict_types=1);

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
