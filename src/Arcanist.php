<?php declare(strict_types=1);

namespace Sassnowski\Arcanist;

use Illuminate\Support\Facades\Route;

class Arcanist
{
    public static function boot(array $assistants): void
    {
        foreach ($assistants as $assistant) {
            static::registerRoutes($assistant);
        }
    }

    private static function registerRoutes(string $assistant): void
    {
        Route::get(
            "/assistant/{$assistant::$slug}",
            "{$assistant}@create"
        )->name("assistant.{$assistant::$slug}.create");

        Route::post(
            "/assistant/{$assistant::$slug}",
            "{$assistant}@store"
        )->name("assistant.{$assistant::$slug}.store");

        Route::get(
            "/assistant/{$assistant::$slug}/{assistantId}/{slug?}",
            "{$assistant}@show"
        )->name("assistant.{$assistant::$slug}.show");

        Route::post(
            "/assistant/{$assistant::$slug}/{assistantId}/{slug}",
            "{$assistant}@update"
        )->name("assistant.{$assistant::$slug}.update");

        Route::delete(
            "/assistant/{$assistant::$slug}/{assistantId}",
            "{$assistant}@destroy"
        )->name("assistant.{$assistant::$slug}.delete");
    }
}
