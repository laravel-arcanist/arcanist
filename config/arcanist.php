<?php declare(strict_types=1);

use Sassnowski\Arcanist\Renderer\BladeResponseRenderer;
use Sassnowski\Arcanist\Repository\DatabaseWizardRepository;

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
    */
    'wizard_repository' => DatabaseWizardRepository::class,

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
    ],
];
