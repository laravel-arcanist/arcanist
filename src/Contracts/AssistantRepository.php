<?php declare(strict_types=1);

namespace Sassnowski\Arcanist\Contracts;

use Sassnowski\Arcanist\AbstractAssistant;
use Sassnowski\Arcanist\Exception\AssistantNotFoundException;

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
