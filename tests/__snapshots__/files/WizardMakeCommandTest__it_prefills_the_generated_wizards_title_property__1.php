<?php

namespace App\Wizards\TestWizard;

use Arcanist\AbstractWizard;
use Arcanist\NullAction;
use Illuminate\Http\Request;

class TestWizard extends AbstractWizard
{
    /**
     * The display name of this wizard.
     */
    public static string $title = 'TestWizard';

    /**
     * The slug of this wizard that gets used in the URL.
     */
    public static string $slug = 'test-wizard';

    /**
     * The action that gets executed after the last step of the
     * wizard is completed.
     */
    protected string $onCompleteAction = NullAction::class;

    protected array $steps = [];

    /**
     * Here you can define additional middleware for a wizard
     * that gets merged together with the global middleware
     * defined in the config.
     */
    public static function middleware(): array
    {
        return [];
    }

    /**
     * Data that should be shared with every step in this
     * wizard. This data gets merged with a step's view data.
     */
    public function sharedData(Request $request): array
    {
        return [];
    }

    /**
     * Return a structured object of the wizard's data that will be
     * passed to the action after the wizard is completed.
     */
    protected function transformWizardData(): mixed
    {
        return parent::transformWizardData();
    }
}
