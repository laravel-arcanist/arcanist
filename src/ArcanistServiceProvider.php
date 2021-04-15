<?php declare(strict_types=1);

namespace Sassnowski\Arcanist;

use function database_path;
use Illuminate\Support\ServiceProvider;

class ArcanistServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations'),
        ], ['migrations', 'arcanist-migrations']);

        $this->publishes([
            __DIR__ . '/../database/config/arcanist.php' => config_path('arcanist.php'),
        ], ['config', 'arcanist-config']);

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/arcanist.php', 'arcanist');
    }
}
