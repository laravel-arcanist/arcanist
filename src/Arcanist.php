<?php declare(strict_types=1);

namespace Sassnowski\Arcanist;

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
        $middleware = array_merge($defaultMiddleware, $wizard::middleware());

        Route::middleware($middleware)
            ->group(function () use ($wizard, $routePrefix) {
                Route::get(
                    "/{$routePrefix}/{$wizard::$slug}",
                    "{$wizard}@create"
                )->name("wizard.{$wizard::$slug}.create");

                Route::post(
                    "/{$routePrefix}/{$wizard::$slug}",
                    "{$wizard}@store"
                )->name("wizard.{$wizard::$slug}.store");

                Route::get(
                    "/{$routePrefix}/{$wizard::$slug}/{wizardId}/{slug?}",
                    "{$wizard}@show"
                )->name("wizard.{$wizard::$slug}.show");

                Route::post(
                    "/{$routePrefix}/{$wizard::$slug}/{wizardId}/{slug}",
                    "{$wizard}@update"
                )->name("wizard.{$wizard::$slug}.update");

                Route::delete(
                    "/{$routePrefix}/{$wizard::$slug}/{wizardId}",
                    "{$wizard}@destroy"
                )->name("wizard.{$wizard::$slug}.delete");
            });
    }
}
