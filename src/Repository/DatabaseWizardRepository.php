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

namespace Arcanist\Repository;

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
            'class' => $wizard::class,
        ])->delete();

        if (0 === $affectedRows) {
            return;
        }

        $wizard->setId(null);
    }

    private function createWizard(AbstractWizard $wizard, array $data): void
    {
        $model = Wizard::create([
            'class' => $wizard::class,
            'data' => $data,
        ]);

        $wizard->setId($model->id);
    }

    private function updateWizard(AbstractWizard $wizard, array $data): void
    {
        $model = $this->loadWizard($wizard);

        $model->update([
            'data' => \array_merge($model->data, $data),
        ]);
    }

    /**
     * @throws WizardNotFoundException
     */
    private function loadWizard(AbstractWizard $wizard): Wizard
    {
        $model = Wizard::where([
            'id' => $wizard->getId(),
            'class' => $wizard::class,
        ])->first();

        if (null === $model) {
            throw new WizardNotFoundException();
        }

        return $model;
    }
}
