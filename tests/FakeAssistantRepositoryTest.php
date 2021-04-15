<?php declare(strict_types=1);

namespace Tests;

use Sassnowski\Arcanist\Contracts\AssistantRepository;
use Sassnowski\Arcanist\Repository\FakeAssistantRepository;

class FakeAssistantRepositoryTest extends TestCase
{
    use AssistantRepositoryContractTests;

    protected function createRepository(): AssistantRepository
    {
        return new FakeAssistantRepository();
    }
}
