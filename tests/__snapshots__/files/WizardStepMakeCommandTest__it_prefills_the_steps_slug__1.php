<?php declare(strict_types=1);

namespace App\Wizards\TestWizard\Steps;

use Arcanist\Field;
use Arcanist\WizardStep;
use Illuminate\Http\Request;

class Step1 extends WizardStep
{
    /**
     * The name of the step that gets displayed in the step list.
     */
    public string $title = 'Step1';

    /**
     * The slug of the wizard that is used in the URL.
     */
    public string $slug = 'step1';

    /**
     * Returns the view data for the template.
     */
    public function viewData(Request $request): array
    {
        return parent::viewData($request);
    }

    public function fields(): array
    {
        return [
            // Field::make('username')
            //     ->rules(['required', 'unique:users,username'])
        ];
    }
}
