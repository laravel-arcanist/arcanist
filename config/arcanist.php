<?php declare(strict_types=1);

use Sassnowski\Arcanist\Repository\DatabaseWizardRepository;

return [
    'redirect_url' => '/home',

    'wizards' => [
        // ...
    ],

    'wizard_repository' => DatabaseWizardRepository::class,

    'renderers' => [
        'blade' => [
            'view_base_path' => 'wizards',
        ],
    ],
];
