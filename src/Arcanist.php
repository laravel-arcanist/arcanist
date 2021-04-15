<?php declare(strict_types=1);

namespace Sassnowski\Arcanist;

use Illuminate\Support\Facades\Route;

class Arcanist
{
    public static function boot(array $wizards): void
    {
        foreach ($wizards as $wizard) {
            static::registerRoutes($wizard);
        }
    }

    private static function registerRoutes(string $wizard): void
    {
        Route::get(
            "/wizard/{$wizard::$slug}",
            "{$wizard}@create"
        )->name("wizard.{$wizard::$slug}.create");

        Route::post(
            "/wizard/{$wizard::$slug}",
            "{$wizard}@store"
        )->name("wizard.{$wizard::$slug}.store");

        Route::get(
            "/wizard/{$wizard::$slug}/{wizardId}/{slug?}",
            "{$wizard}@show"
        )->name("wizard.{$wizard::$slug}.show");

        Route::post(
            "/wizard/{$wizard::$slug}/{wizardId}/{slug}",
            "{$wizard}@update"
        )->name("wizard.{$wizard::$slug}.update");

        Route::delete(
            "/wizard/{$wizard::$slug}/{wizardId}",
            "{$wizard}@destroy"
        )->name("wizard.{$wizard::$slug}.delete");
    }
}
