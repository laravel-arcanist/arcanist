<?php declare(strict_types=1);

namespace Sassnowski\Arcanist\Assistant\Contracts;

use Sassnowski\Arcanist\Assistant\AbstractAssistant;
use Sassnowski\Arcanist\Assistant\Exception\AssistantNotFoundException;

interface AssistantRepository
{
    /**
     * @return AbstractAssistant[]
     */
    public function registeredAssistants(): array;

    /**
     * @throws AssistantNotFoundException
     */
    public function saveData(AbstractAssistant $assistant, array $data): void;

    /**
     * @throws AssistantNotFoundException
     */
    public function loadData(AbstractAssistant $assistant): array;

    public function deleteAssistant(AbstractAssistant $assistant): void;
}
