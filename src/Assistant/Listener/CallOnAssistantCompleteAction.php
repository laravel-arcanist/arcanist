<?php declare(strict_types=1);

namespace Sassnowski\Arcanist\Assistant\Listener;

use Sassnowski\Arcanist\Assistant\Event\AssistantFinishing;

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
