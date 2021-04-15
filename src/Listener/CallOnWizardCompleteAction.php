<?php declare(strict_types=1);

namespace Sassnowski\Arcanist\Listener;

use Sassnowski\Arcanist\Event\WizardFinishing;

class CallOnWizardCompleteAction
{
    public function handle(WizardFinishing $event): void
    {
        $action = app()->make($event->wizard->onCompleteAction);

        $result = $action->execute($event->wizard->transformWizardData());

        if ($result !== null) {
            $event->wizard->setData('completedPayload', $result);
        }
    }
}
