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

use Arcanist\ArcanistServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use PHPUnit\Framework\Assert as PHPUnitAssert;

class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * @param array<int, string> $middlewares
     */
    public function assertRouteUsesMiddleware(string $routeName, array $middlewares, bool $exact = false): void
    {
        $router = resolve(Router::class);

        $router->getRoutes()
            ->refreshNameLookups();

        $route = $router->getRoutes()->getByName($routeName);

        if (null === $route) {
            PHPUnitAssert::fail("Unable to find route for name `{$routeName}`");
        }

        $usedMiddlewares = $route->gatherMiddleware();

        if ($exact) {
            $unusedMiddlewares = \array_diff($middlewares, $usedMiddlewares);
            $extraMiddlewares = \array_diff($usedMiddlewares, $middlewares);

            $messages = [];

            if ($extraMiddlewares) {
                $messages[] = 'uses unexpected `' . \implode(', ', $extraMiddlewares) . '` middlware(s)';
            }

            if ($unusedMiddlewares) {
                $messages[] = "doesn't use expected `" . \implode(', ', $unusedMiddlewares) . '` middlware(s)';
            }

            $messages = \implode(' and ', $messages);

            PHPUnitAssert::assertSame(\count($unusedMiddlewares) + \count($extraMiddlewares), 0, "Route `{$routeName}` " . $messages);
        } else {
            $unusedMiddlewares = \array_diff($middlewares, $usedMiddlewares);

            PHPUnitAssert::assertSame(\count($unusedMiddlewares), 0, "Route `{$routeName}` does not use expected `" . \implode(', ', $unusedMiddlewares) . '` middleware(s)');
        }
    }

    /**
     * @param mixed $app
     *
     * @return array<int, class-string<ServiceProvider>>
     */
    protected function getPackageProviders($app): array
    {
        return [
            ArcanistServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        include_once __DIR__ . '/../database/migrations/create_wizards_table.php.stub';

        /** @phpstan-ignore-next-line */
        (new \CreateWizardsTable())->up();
    }
}
