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

use Arcanist\Action\WizardAction;
use Arcanist\Contracts\WizardActionResolver;
use Arcanist\Resolver\ContainerWizardActionResolver;
use Mockery as m;

class ContainerWizardActionResolverTest extends TestCase
{
    public function testItImplementsTheCorrectInterface(): void
    {
        self::assertInstanceOf(
            WizardActionResolver::class,
            new ContainerWizardActionResolver(),
        );
    }

    public function testItResolvesTheActionFromTheContainer(): void
    {
        $expected = m::mock(WizardAction::class);
        app()->instance('::action::', $expected);

        $actual = (new ContainerWizardActionResolver())->resolveAction('::action::');

        self::assertEquals($expected, $actual);
    }
}
