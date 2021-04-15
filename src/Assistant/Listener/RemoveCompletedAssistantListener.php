<?php declare(strict_types=1);

namespace Sassnowski\Arcanist\Assistant\Listener;

use Sassnowski\Arcanist\Assistant\Event\AssistantFinished;
use Sassnowski\Arcanist\Assistant\Contracts\AssistantRepository;

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
