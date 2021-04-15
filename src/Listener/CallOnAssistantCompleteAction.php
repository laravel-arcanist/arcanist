<?php declare(strict_types=1);

namespace Sassnowski\Arcanist\Listener;

use Sassnowski\Arcanist\Event\AssistantFinishing;

class CallOnAssistantCompleteAction
{
    public function handle(AssistantFinishing $event): void
    {
        $action = app()->make($event->assistant->onCompleteAction);

        $result = $action->execute($event->assistant->transformAssistantData());

        if ($result !== null) {
            $event->assistant->setData('completedPayload', $result);
        }
    }
}
