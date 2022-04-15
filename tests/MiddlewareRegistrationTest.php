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
use Arcanist\Arcanist;

class MiddlewareRegistrationTest extends TestCase
{
    public function testItRegistersAllWizardRoutesWithTheMiddlewareDefinedInTheConfig(): void
    {
        config(['arcanist.middleware' => ['web']]);

        Arcanist::boot([NoMiddlewareWizard::class]);

        $this->assertRouteUsesMiddleware('wizard.no-middleware.create', ['web'], true);
    }

    public function testItMergesMiddlewareDefinedOnTheWizardWithMiddlewareFromConfig(): void
    {
        config(['arcanist.middleware' => ['web']]);

        Arcanist::boot([ExtraMiddlewareWizard::class]);

        $this->assertRouteUsesMiddleware('wizard.extra-middleware.create', ['web', 'auth'], true);
    }
}

class NoMiddlewareWizard extends AbstractWizard
{
    public static string $slug = 'no-middleware';
}

class ExtraMiddlewareWizard extends AbstractWizard
{
    public static string $slug = 'extra-middleware';

    public static function middleware(): array
    {
        return ['auth'];
    }
}
