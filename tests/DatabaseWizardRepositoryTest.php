<?php declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Sassnowski\Arcanist\Contracts\WizardRepository;
use Sassnowski\Arcanist\Repository\DatabaseWizardRepository;

class DatabaseWizardRepositoryTest extends TestCase
{
    use RefreshDatabase, WizardRepositoryContractTests;

    protected function createRepository(): WizardRepository
    {
        return new DatabaseWizardRepository();
    }
}
