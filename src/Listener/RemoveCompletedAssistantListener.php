<?php declare(strict_types=1);

namespace Sassnowski\Arcanist\Listener;

use Sassnowski\Arcanist\Event\AssistantFinished;
use Sassnowski\Arcanist\Contracts\AssistantRepository;

class RemoveCompletedAssistantListener
{
    public function __construct(private AssistantRepository $repository)
    {
    }

    public function handle(AssistantFinished $event): void
    {
        $this->repository->deleteAssistant($event->assistant);
    }
}
