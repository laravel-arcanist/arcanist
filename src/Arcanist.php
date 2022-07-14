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

namespace Arcanist;

use Illuminate\Support\Facades\Route;

class Arcanist
{
    /**
     * @param array<int, class-string<AbstractWizard>> $wizards
     */
    public static function boot(array $wizards): void
    {
        /** @var string $routePrefix */
        $routePrefix = config('arcanist.route_prefix');

        /** @var array<int, mixed> $defaultMiddleware */
        $defaultMiddleware = config('arcanist.middleware', []);

        foreach ($wizards as $wizard) {
            self::registerRoutes($wizard, $routePrefix, $defaultMiddleware);
        }
    }

    /**
     * @param class-string<AbstractWizard> $wizard
     * @param array<int, mixed>            $defaultMiddleware
     */
    private static function registerRoutes(string $wizard, string $routePrefix, array $defaultMiddleware): void
    {
        $middleware = \array_merge($defaultMiddleware, $wizard::middleware());

        Route::middleware($middleware)
            ->group(function () use ($wizard, $routePrefix): void {
                Route::get(
                    "/{$routePrefix}/{$wizard::$slug}",
                    "{$wizard}@create",
                )->name("wizard.{$wizard::$slug}.create");

                Route::post(
                    "/{$routePrefix}/{$wizard::$slug}",
                    "{$wizard}@store",
                )->name("wizard.{$wizard::$slug}.store");

                Route::get(
                    "/{$routePrefix}/{$wizard::$slug}/{wizardId}/{slug?}",
                    "{$wizard}@show",
                )->name("wizard.{$wizard::$slug}.show");

                Route::post(
                    "/{$routePrefix}/{$wizard::$slug}/{wizardId}/{slug}",
                    "{$wizard}@update",
                )->name("wizard.{$wizard::$slug}.update");

                Route::delete(
                    "/{$routePrefix}/{$wizard::$slug}/{wizardId}",
                    "{$wizard}@destroy",
                )->name("wizard.{$wizard::$slug}.delete");
            });
    }
}
