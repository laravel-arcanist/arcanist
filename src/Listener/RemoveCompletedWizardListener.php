<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/laravel-arcanist/arcanist
 */

namespace Arcanist\Listener;

use Arcanist\Contracts\WizardRepository;
use Arcanist\Event\WizardFinished;

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
