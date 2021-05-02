<?php declare(strict_types=1);

use Arcanist\Renderer\BladeResponseRenderer;
use Arcanist\Repository\DatabaseWizardRepository;
use Arcanist\Resolver\ContainerWizardActionResolver;

return [
    /*
    |--------------------------------------------------------------------------
    | Redirect URL
    |--------------------------------------------------------------------------
    |
    | The default URL the user gets redirected to after a Wizard
    | was completed. This can be overwritten for a particular
    | wizard by implementing the `redirectTo()` method.
    |
    */
    'redirect_url' => '/home',

    /*
    |--------------------------------------------------------------------------
    | Registered Wizards
    |--------------------------------------------------------------------------
    |
    | Here you can specify all wizards that should be registered when
    | your application starts. Only wizards that are configured
    | here will be available.
    |
    */
    'wizards' => [
        // \App\Wizards\RegistrationWizard::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Wizard Repository
    |--------------------------------------------------------------------------
    |
    | Here you can configure the wizard repository that gets used
    | to save and retrieve a wizard's state between steps. This
    | package ships with an Eloquent-based implementation.
    |
    | The `ttl` setting determines how much time is allowed to pass (in seconds)
    | without an update before a wizard is considered `expired` and can
    | be deleted.
    |
    */
    'storage' => [
        'driver' => DatabaseWizardRepository::class,
        'ttl' => 24 * 60 * 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Wizard Action Resolver
    |--------------------------------------------------------------------------
    |
    | By default, Arcanist resolves all action out of the Laravel
    | service container. If you need a different behavior, you
    | can provide a different implementation here.
    |
    */
    'action_resolver' => ContainerWizardActionResolver::class,

    /*
    |--------------------------------------------------------------------------
    | Response Renderers
    |--------------------------------------------------------------------------
    |
    | This is where you can configure which response renderer
    | Arcanist should use, as well as renderer-specific
    | configuration.
    |
    */
    'renderers' => [
        'renderer' => BladeResponseRenderer::class,

        'blade' => [
            'view_base_path' => 'wizards',
        ],

        'inertia' => [
            'component_base_path' => 'Wizards',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | Here you can change the route prefix that gets added
    | to the URLs of each wizard.
    |
    */
    'route_prefix' => 'wizard',

    /*
    |--------------------------------------------------------------------------
    | Wizard Middleware
    |--------------------------------------------------------------------------
    |
    | This is where you can default the default middleware group
    | that gets applied to all routes of all wizards. You can
    | customize it inside each wizard by overwriting the static
    | `middleware` method.
    |
    | Note: Any middleware defined on a wizard gets *merged* with
    | this middleware instead of replacing it.
    |
    */
    'middleware' => ['web'],
];
