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

namespace Arcanist\Tests;

use Arcanist\AbstractWizard;
use Arcanist\Event\WizardFinished;
use Arcanist\Listener\RemoveCompletedWizardListener;
use Arcanist\Repository\FakeWizardRepository;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class RemoveCompletedWizardListenerTest extends TestCase
{
    public function testItRemovesAWizardAfterItWasCompleted(): void
    {
        $wizard = m::mock(AbstractWizard::class)->makePartial();
        $wizard->setId(1);
        $repository = new FakeWizardRepository();
        $event = new WizardFinished($wizard);
        $listener = new RemoveCompletedWizardListener($repository);

        $listener->handle($event);

        self::assertFalse($wizard->exists());
    }
}
