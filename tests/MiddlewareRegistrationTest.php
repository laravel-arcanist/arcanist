<?php declare(strict_types=1);

namespace Arcanist\Tests;

use Arcanist\Arcanist;
use Arcanist\AbstractWizard;

class MiddlewareRegistrationTest extends TestCase
{
    /** @test */
    public function it_registers_all_wizard_routes_with_the_middleware_defined_in_the_config(): void
    {
        config(['arcanist.middleware' => ['web']]);

        Arcanist::boot([NoMiddlewareWizard::class]);

        $this->assertRouteUsesMiddleware('wizard.no-middleware.create', ['web'], true);
    }

    /** @test */
    public function it_merges_middleware_defined_on_the_wizard_with_middleware_from_config(): void
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
