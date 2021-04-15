<?php declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Sassnowski\Arcanist\Contracts\AssistantRepository;
use Sassnowski\Arcanist\Repository\DatabaseAssistantRepository;

class DatabaseAssistantRepositoryTest extends TestCase
{
    use RefreshDatabase, AssistantRepositoryContractTests;

    protected function createRepository(): AssistantRepository
    {
        return new DatabaseAssistantRepository();
    }
}
