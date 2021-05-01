<?php declare(strict_types=1);

namespace Arcanist\Repository;

use function get_class;
use function array_merge;
use Arcanist\AbstractWizard;
use Arcanist\Contracts\WizardRepository;
use Arcanist\Exception\WizardNotFoundException;

class DatabaseWizardRepository implements WizardRepository
{
    public function saveData(AbstractWizard $wizard, array $data): void
    {
        $wizard->exists()
            ? $this->updateWizard($wizard, $data)
            : $this->createWizard($wizard, $data);
    }

    public function loadData(AbstractWizard $wizard): array
    {
        return $this->loadWizard($wizard)->data;
    }

    public function deleteWizard(AbstractWizard $wizard): void
    {
        $affectedRows = Wizard::where([
            'id' => $wizard->getId(),
            'class' => get_class($wizard),
        ])->delete();

        if ($affectedRows === 0) {
            throw new WizardNotFoundException();
        }

        $wizard->setId(null);
    }

    private function createWizard(AbstractWizard $wizard, array $data): void
    {
        $model = Wizard::create([
            'class' => get_class($wizard),
            'data' => $data,
        ]);

        $wizard->setId($model->id);
    }

    private function updateWizard(AbstractWizard $wizard, array $data): void
    {
        $model = $this->loadWizard($wizard);

        $model->update([
            'data' => array_merge($model->data, $data)
        ]);
    }

    /**
     * @throws WizardNotFoundException
     */
    private function loadWizard(AbstractWizard $wizard): Wizard
    {
        $model = Wizard::where([
            'id' => $wizard->getId(),
            'class' => get_class($wizard)
        ])->first();

        if ($model === null) {
            throw new WizardNotFoundException();
        }

        return $model;
    }
}
