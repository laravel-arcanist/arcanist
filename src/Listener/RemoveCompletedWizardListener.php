<?php declare(strict_types=1);

namespace Arcanist\Listener;

use Arcanist\Event\WizardFinished;
use Arcanist\Contracts\WizardRepository;

class RemoveCompletedWizardListener
{
    public function __construct(private WizardRepository $repository)
    {
    }

    public function handle(WizardFinished $event): void
    {
        $this->repository->deleteWizard($event->wizard);
    }
}
