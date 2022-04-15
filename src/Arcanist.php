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
    public static function boot(array $wizards): void
    {
        $routePrefix = config('arcanist.route_prefix');
        $defaultMiddleware = config('arcanist.middleware', []);

        foreach ($wizards as $wizard) {
            static::registerRoutes($wizard, $routePrefix, $defaultMiddleware);
        }
    }

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
