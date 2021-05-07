<?php declare(strict_types=1);

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

        $wizardClass = get_class($wizard);

        $existingData = $this->data[$wizardClass][$wizard->getId()] ?? [];

        $this->data[$wizardClass][$wizard->getId()] = array_merge($existingData, $data);
    }

    public function loadData(AbstractWizard $wizard): array
    {
        $wizardClass = get_class($wizard);

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

        unset($this->data[get_class($wizard)][$wizard->getId()]);

        $wizard->setId(null);
    }

    private function guardAgainstWizardClassMismatch(AbstractWizard $wizard): void
    {
        throw_if($this->hasIdMismatch($wizard), new WizardNotFoundException());
    }

    private function hasIdMismatch(AbstractWizard $wizard): bool
    {
        return collect($this->data)
            ->except(get_class($wizard))
            ->contains(fn (array $wizards) => isset($wizards[$wizard->getId()]));
    }
}
