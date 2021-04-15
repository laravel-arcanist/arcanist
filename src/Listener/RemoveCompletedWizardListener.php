<?php declare(strict_types=1);

namespace Sassnowski\Arcanist\Listener;

use Sassnowski\Arcanist\Event\WizardFinished;
use Sassnowski\Arcanist\Contracts\WizardRepository;

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
