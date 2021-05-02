<?php declare(strict_types=1);

namespace Arcanist\Tests;

use Arcanist\Contracts\WizardRepository;
use Arcanist\Repository\DatabaseWizardRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DatabaseWizardRepositoryTest extends TestCase
{
    use RefreshDatabase, WizardRepositoryContractTests;

    protected function createRepository(): WizardRepository
    {
        return new DatabaseWizardRepository();
    }
}
