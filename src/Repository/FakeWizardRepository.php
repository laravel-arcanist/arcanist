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

class FakeWizardRepository implements WizardRepository
{
    private int $nextId = 1;

    public function __construct(private array $data = [])
    {
    }

    public function saveData(AbstractWizard $wizard, array $data): void
    {
        if ($wizard->getId() === null) {
            $wizard->setId($this->nextId++);
        }

        $this->guardAgainstWizardClassMismatch($wizard);

        $wizardClass = $wizard::class;

        $existingData = $this->data[$wizardClass][$wizard->getId()] ?? [];

        $this->data[$wizardClass][$wizard->getId()] = \array_merge($existingData, $data);
    }

    public function loadData(AbstractWizard $wizard): array
    {
        $wizardClass = $wizard::class;

        if (!isset($this->data[$wizardClass][$wizard->getId()])) {
            throw new WizardNotFoundException();
        }

        return $this->data[$wizardClass][$wizard->getId()];
    }

    public function deleteWizard(AbstractWizard $wizard): void
    {
        if ($this->hasIdMismatch($wizard)) {
            return;
        }

        unset($this->data[$wizard::class][$wizard->getId()]);

        $wizard->setId(null);
    }

    private function guardAgainstWizardClassMismatch(AbstractWizard $wizard): void
    {
        throw_if($this->hasIdMismatch($wizard), new WizardNotFoundException());
    }

    private function hasIdMismatch(AbstractWizard $wizard): bool
    {
        return collect($this->data)
            ->except($wizard::class)
            ->contains(fn (array $wizards) => isset($wizards[$wizard->getId()]));
    }
}
