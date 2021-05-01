<?php declare(strict_types=1);

namespace Arcanist\Contracts;

use Arcanist\AbstractWizard;
use Arcanist\Exception\WizardNotFoundException;

interface WizardRepository
{
    /**
     * @throws WizardNotFoundException
     */
    public function saveData(AbstractWizard $wizard, array $data): void;

    /**
     * @throws WizardNotFoundException
     */
    public function loadData(AbstractWizard $wizard): array;

    /**
     * @throws WizardNotFoundException
     */
    public function deleteWizard(AbstractWizard $wizard): void;
}
