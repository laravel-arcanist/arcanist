<?php declare(strict_types=1);

namespace Sassnowski\Arcanist;

use function database_path;
use Illuminate\Support\ServiceProvider;
use Sassnowski\Arcanist\Event\WizardFinished;
use Sassnowski\Arcanist\Contracts\ResponseRenderer;
use Sassnowski\Arcanist\Contracts\WizardRepository;
use Sassnowski\Arcanist\Contracts\WizardActionResolver;
use Sassnowski\Arcanist\Renderer\BladeResponseRenderer;
use Sassnowski\Arcanist\Listener\RemoveCompletedWizardListener;

class ArcanistServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations'),
        ], ['migrations', 'arcanist-migrations']);

        $this->publishes([
            __DIR__ . '/../config/arcanist.php' => config_path('arcanist.php'),
        ], ['config', 'arcanist-config']);

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        Arcanist::boot($this->app['config']['arcanist']['wizards']);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/arcanist.php', 'arcanist');

        $this->app['events']->listen(
            WizardFinished::class,
            RemoveCompletedWizardListener::class
        );

        $this->app->bind(
            WizardRepository::class,
            $this->app['config']['arcanist']['wizard_repository']
        );

        $this->app->bind(
            ResponseRenderer::class,
            $this->app['config']['arcanist']['renderers']['renderer']
        );

        $this->app->bind(
            WizardActionResolver::class,
            $this->app['config']['arcanist']['action_resolver']
        );

        $this->app->when(BladeResponseRenderer::class)
            ->needs('$viewBasePath')
            ->give($this->app['config']['arcanist']['renderers']['blade']['view_base_path']);
    }
}
