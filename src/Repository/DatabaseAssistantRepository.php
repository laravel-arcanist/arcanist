<?php declare(strict_types=1);

namespace Sassnowski\Arcanist\Repository;

use function get_class;
use function array_merge;
use Sassnowski\Arcanist\AbstractAssistant;
use Sassnowski\Arcanist\Contracts\AssistantRepository;
use Sassnowski\Arcanist\Exception\AssistantNotFoundException;

class DatabaseAssistantRepository implements AssistantRepository
{
    public function __construct(private array $registeredAssistants = [])
    {
    }

    public function registeredAssistants(): array
    {
        return $this->registeredAssistants;
    }

    public function saveData(AbstractAssistant $assistant, array $data): void
    {
        $assistant->exists()
            ? $this->updateAssistant($assistant, $data)
            : $this->createAssistant($assistant, $data);
    }

    public function loadData(AbstractAssistant $assistant): array
    {
        return $this->loadAssistant($assistant)->data;
    }

    public function deleteAssistant(AbstractAssistant $assistant): void
    {
        $affectedRows = Assistant::where([
            'id' => $assistant->getId(),
            'class' => get_class($assistant),
        ])->delete();

        if ($affectedRows === 0) {
            throw new AssistantNotFoundException();
        }

        $assistant->setId(null);
    }

    private function createAssistant(AbstractAssistant $assistant, array $data): void
    {
        $model = Assistant::create([
            'class' => get_class($assistant),
            'data' => $data,
        ]);

        $assistant->setId($model->id);
    }

    private function updateAssistant(AbstractAssistant $assistant, array $data): void
    {
        $model = $this->loadAssistant($assistant);

        $model->update([
            'data' => array_merge($model->data, $data)
        ]);
    }

    /**
     * @throws AssistantNotFoundException
     */
    private function loadAssistant(AbstractAssistant $assistant): Assistant
    {
        $model = Assistant::where([
            'id' => $assistant->getId(),
            'class' => get_class($assistant)
        ])->first();

        if ($model === null) {
            throw new AssistantNotFoundException();
        }

        return $model;
    }
}
